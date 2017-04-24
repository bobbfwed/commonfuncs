<?PHP
/***** Backup Databases *********************************************************
 *                                                                              *
 * Version: 1.0.3                                                               *
 * Date: April 24, 2017                                                         *
 *                                                                              *
 * Requires PHP 5.3 or higher and a PDO connection to a MySQL database.         *
 * Probably if the strict_type and hints were removed, and the                  *
 * ...file($dirname . $archive_info_filename)[1]; line was changed, it could    *
 * support significantly older versions of PHP (maybe 4.3 or so).               *
 *                                                                              *
 * Written by: Brandon Nimon                                                    *
 * Copyright: Brandon Nimon                                                     *
 * Email: brandon@nimonpro.com                                                  *
 *                                                                              *
 * This class backs up a database to a local file. The format is standard SQL,  *
 * intended to be used with a MySQL database. There is a chance that it will    *
 * work with other database types too, though I kind of doubt it.               *
 *                                                                              *
 * The only public method in this class is the backup_database() method. This   *
 * method backs up all tables in a given database to a local file. By default,  *
 * when the data differs from the previous backup, a file is created for each   *
 * day. If the current data is the same as the previous backup, nothing is      *
 * saved. This may appear as date gaps between backups, it just means nothing   *
 * has changed from then until present. On the other hand, if the data differs, *
 * but the $date_format output is the same, the old data is overwritten. This   *
 * means two things:                                                            *
 *                                                                              *
 *  1) Only the latest version of the database is saved for any given           *
 *     $date_format output, and...                                              *
 *                                                                              *
 *  2) How many (and for how long) backups are kept can be loosely managed by   *
 *     changing the $date_format value.                                         *
 *     For example, removing the 'Y' from the default value would effectively   *
 *     keep backups for a maximum of a year. During the next year, old data     *
 *     would be overwritten as each date passed. While replacing the 'm-d' with *
 *     a 'W' would maintain only one backup per week of the year.               *
 *                                                                              *
 * Settings can be changed, such as where the files are saved to, what type of  *
 * linebreak the files should have, whether or not the database backups should  *
 * be in their own subdirectories, if the output file should be compressed, and *
 * how often INSERT commands should be broken up, and more. Read the comments   *
 * for all the public variables in the class for more information.              *
 *                                                                              *
 * Some additional notes:                                                       *
 *   * When a table is created, the AUTO_INCREMENT values are maintained, even  *
 *     if the table is backed up as "structure only".                           *
 *   * NULL Values are maintained (a somewhat uncommon feature of PHP database  *
 *     backups).                                                                *
 *   * The program is untested with high ANSI characters, but it may work since *
 *     outputed strings are escaped.                                            *
 *                                                                              *
 * Change Log:                                                                  *
 *   1.0.0 (28 Sep 2016): Initial release.                                      *
 *   1.0.1 (19 Oct 2016): Minor optimizations.                                  *
 *   1.0.2 (21 Apr 2017):                                                       *
 *     Added support for backing up triggers.                                   *
 *     Added support for non-default delimiters.                                *
 *     Added ability to change archive_info_filename.                           *
 *   1.0.3 (24 Apr 2017): Added support for foreign key constraints.            *
 *                                                                              *
 *                                                                              *
 * EXAMPLE USE:                                                                 *
 * <?PHP                                                                        *
 *                                                                              *
 * require('backup_databases.class.php'); // this file                          *
 * require('db_connection.php'); // you provide the DB connection               *
 *                                                                              *
 * $backup_dbs = new backup_databases; // new instance of the class             *
 *                                                                              *
 * // change a setting like this:                                               *
 * $backup_dbs->save_location = 'E:/backups/my_real_save_directory/';           *
 *                                                                              *
 * // backup more databases by running the following 4 lines, and only changing *
 * // the $db_conn to a different database                                      *
 * if ($backup_dbs->backup_database($pdo_db_conn)) // perform backup            *
 *   echo 'New backup created.'; // Success!                                    *
 * else                                                                         *
 *   echo 'No new backup.'; // either data was the same, or there was an error  *
 *                                                                              *
 ********************************************************************************/
declare(strict_types=1);

class backup_databases {

  private $Version = '1.0.3';

  public $save_location = 'C:/db-archives/';    // Requires ending slash ("/"). Best if absolute location.
  public $date_format = 'Y-m-d';                // PHP date() format - this is appended to the file name. This can be used to manage how long old backups are stored, and other overwrting features (if you get createive).
  public $nl = "\n";                            // Output file's line break / new line (this does not affect MySQL queries) -- generally: "\n" = UNIX, "\r" = Mac, or "\r\n" = Windows
  public $archive_info_filename = 'info.txt';   // A file to store information about the latest archive (such as timestamp and md5 of the backup), file is stored in the same directory as the backup, and is overwritten when each new backup is created
  public $use_database_subdirectory = true;     // Place output file in a subdirectory, the subdirectory's name will be the name of the database
  public $compress = true;                      // Use GZIP to compress each database.
  public $value_sets_between_inserts = 1000;    // Breaking up INSERTs can be useful on very large tables, use this value to adjust how often values in an INSERT are broken into separate SQL commands
  public $backup_foreign_keys = true;           // Foreign key constraints are removed from the CREATE TABLES script to prevent conflicts, this setting determines weather they are added back in at the end of the file or not (note: even tables in the $structure_only_tables will have their foreign key constraints backed up)
  public $backup_triggers = true;               // Add a query at the end of all inserts with all triggers for each database (note: even tables in the $structure_only_tables will have their triggers backed up)
  public $default_delimiter = ';';              // Add a default delimieter to the file, I can't foresee a reason this should be anything other than ";", but maybe someone will find a reason
  public $trigger_delimiter = '$$';             // Delimiter for trigger section, it can be anything as long as it is not ';' and not something that will come up in triggers and queries

  // these shouldn't need to be changed between uses, should probably be constants
  protected $num_native_types = array('NEWDECIMAL' => 1, 'FLOAT' => 1, 'DOUBLE' => 1, 'TINY' => 1, 'LONG' => 1, 'LONGLONG' => 1, 'INT24' => 1, 'SHORT' => 1); // MySQL PDO types that correspond to numbers (they don't require quotes "" during insertion)


  private $foreign_keys = array();              // store foreign key constraints (removed from the CREATE TABLE string and appended to the end of the file to prevent conflicts)


  // Backup a database
  // $db is a PDO connection to a database
  // $tables_to_backup is a list of tables to backup (structure and/or data)
  // $structure_only_tables is a list of tables which only the structure should be backed up (tables in this list will not be backed up at all if not first included in $tables_to_backup)
  // For both table lists: '*' includes all tables, or specify a list of tables using an array or comma-delimited string
  // This method returns true if a new file was written, false if error or nothing was written
  public function backup_database (pdo &$db, $tables_to_backup = '*', $structure_only_tables = array()): bool {

    $stmt = $db->query('SELECT DATABASE()');
    $dbname = $stmt->fetch(PDO::FETCH_COLUMN);                                                    // get database name

    if (!$this->table_list($db, $tables_to_backup))                                               // get list of tables to look through/backup
      return false;

    if (count($tables_to_backup) == 0)                                                            // if nothing to backup, just leave
      return false;

    $this->table_list($db, $structure_only_tables);                                               // get list of tables which only the structure will be backed up

    $this->foreign_keys = array();                                                                // clear any foreign key constraints (incase the same object is used to backup multiple databases)


    // create a header for the SQL file
    $return  = '--' . $this->nl;
    $return .= '-- DB Archive/Backup' . $this->nl;
    $return .= '-- Backup generated by Backup Databases Script v' . $this->Version . $this->nl;
    $return .= '-- Script written by Brandon Nimon' . $this->nl;
    $return .= '-- Git: https://github.com/bobbfwed/commonfuncs' . $this->nl;
    $return .= '--' . $this->nl;
    $return .= '-- DB Name: ' . $dbname . $this->nl;
    $return .= '--' . $this->nl . $this->nl;
    $return .= 'DELIMITER ' . $this->default_delimiter . $this->nl . $this->nl;                   // set delimiter

    // a header for this section
    $return .= '--' . $this->nl;
    $return .= '-- Create Tables' . $this->nl;
    $return .= '--' . $this->nl . $this->nl;

    for ($table_idx = 0; $table_idx < count($tables_to_backup); $table_idx++) {                   // cycle through all of the tables

      $table = trim($tables_to_backup[$table_idx]);                                               // clean up table name (comma-delimted list could likely contain spaces)
      
      $return .= 'DROP TABLE IF EXISTS `'.$table.'`' . $this->default_delimiter;                  // if restoring a database, tables may already exist, remove them

      $createtable_stmt = $db->query('SHOW CREATE TABLE `'.$table.'`');
      list($tablename, $createtable) = $createtable_stmt->fetch(PDO::FETCH_NUM);

      $createtable = str_replace(array("\r\n", "\r", "\n"), $this->nl, $createtable);             // standardize new lines for CREATE TABLE script
      $createtable = $this->remove_foreign_key_constraints($tablename, $createtable);             // remove foreign key constraints and store them in $this->foreign_keys to add them near the very end of the file

      $return .= $this->nl . $this->nl . $createtable . $this->default_delimiter;                 // add create table string -- this is all that is needed for struction only tables

      if (!in_array($table, $structure_only_tables)) {                                            // if the current table is not in the structure only list, then get data too

        $alloftable_stmt = $db->query('SELECT * FROM `'.$table.'`');                              // get all contents
        $num_fields = $alloftable_stmt->columnCount();                                            // get number of columns

        $columnnames_stmt = $db->query('SHOW COLUMNS FROM `'.$table.'`');                         // get the list of columns/fields
        $column_str = '';

        while ($column = $columnnames_stmt->fetch(PDO::FETCH_ASSOC))                              // add each column/field
          $column_str .= '`'.$column['Field'].'`,';

        $column_str = substr($column_str, 0, -1);

        $column_int = array();                                                                    // create array that will track if a specified column is an integer value (does not require quotes)
        for ($i = 0; $i < $num_fields; $i++) {                                                    // cycle through all the of the fields

          $meta = $alloftable_stmt->getColumnMeta($i);
          $column_int[$i] = ($meta['pdo_type'] == PDO::PARAM_INT || (isset($meta['native_type']) && isset($this->num_native_types[$meta['native_type']]))); // if column type is an integer

          // Use this bit of code to find new native_type values
          // if (isset($meta['native_type']) && !isset($str_native_types[$meta['native_type']])
          //   && !isset($this->num_native_types[$meta['native_type']]))
          // {
          //   print_r($meta);
          //   echo '<br>'.$row[$i];
          //   echo '<br><br>'.$this->nl.$this->nl;
          // }

        }


        $insert_count = 0;
        while ($row = $alloftable_stmt->fetch(PDO::FETCH_NUM)) {                                  // cycle through all the data of this table

          if ($insert_count % $this->value_sets_between_inserts == 0) {

            if ($insert_count != 0)                                                               // only if not the first entry
              $return .= $this->default_delimiter;

            $return .= $this->nl . $this->nl . 'INSERT INTO `'.$table.'` (' . $column_str . ') VALUES'; // start insert

          } else
            $return .= ',';

          $return .= $this->nl . '(';                                                             // start values list

          for ($i = 0; $i < $num_fields; $i++) {                                                  // cycle through all the of the fields

            if (is_null($row[$i]))                                                                // if value is null...
              $return .= 'NULL';                                                                  // then add NULL entry

            else {

              if ($column_int[$i])                                                                // if it is an integer...
                $return .= $row[$i];                                                              // insert column without quotes

              else                                                                                // if it is a string...
                $return .= $db->quote($row[$i]);                                                  // insert escaped string

            }

            if ($i < ($num_fields - 1))                                                           // for all but the last column...
              $return .= ',';                                                                     // insert comma

          }

          $return .= ')';                                                                         // end values list

          $insert_count++;

        }

        $return .= $this->default_delimiter;                                                      // end insert

      }

      $return .= $this->nl . $this->nl . $this->nl;

    }


    if ($this->backup_triggers)
      $return .= $this->backup_triggers_by_table($db, $tables_to_backup);                         // put trigger in near the end of the file

    if ($this->backup_foreign_keys)
      $return .= $this->backup_foreign_key_constraints();                                         // put foreign key constraints back in at the end of the file here
    
    return $this->write_file($dbname, $return);

  }


  // convert a table list to a usable array
  // can $table_list can be an array, comma delimted string, or '*' which indicates all tables in a database
  // This method might alter the supplied list if the list is not an array
  // returns false if list is empty, true otherwise
  private function table_list (pdo &$db, &$table_list): bool {

    switch (true) {

      case $table_list == '*':                                                                    // if wildcard, get all tables
        $table_list = array();
        $stmt = $db->query('SHOW TABLES');
        $table_list = $stmt->fetchAll(PDO::FETCH_COLUMN);                                         // fetch all tables from database

        if (count($table_list) == 0)
          return false;
        break;

      case is_array($table_list) && count($table_list) > 0:                                       // if an array with the tables is already supplied, do nothing
        // do nothing to array
        break;

      case is_string($table_list) && trim($table_list) != '':                                     // if string...
        $table_list = explode(',', $table_list);                                                  // parse comma-delimited list into an array
        break;

      default:                                                                                    // if unknown input...
        $table_list = array();                                                                    // empty array
        return false;                                                                             // and return false

    }

    return true;                                                                                  // everything except "default" switch case will return true

  }


  // Remove foreign key constraints from a CREATE TABLE script, since running constraint commands when all the tables have not been created and populated will likely cause conflicts
  // $tablename is the name of the table currently being processed
  // $createtable is the CREATE TABLE script generated by the database
  private function remove_foreign_key_constraints (string $tablename, string $createtable): string {

    $createtable_lines = explode($this->nl, $createtable);                                        // break create table script into individual lines
    $createtable_lines_without_triggers = array();                                                // create new array to hold the create table script with the foreign key constraints removed (since having them all at the top would cause conflicts more often than not)
    $first_constraint = true;

    for ($i = 0; $i < count($createtable_lines); $i++) {                                          // go through each line of the CREATE TABLE script

      if (stripos($createtable_lines[$i], 'CONSTRAINT') !== false) {                              // if this line is a foreign key constraint command
        
        $this->foreign_keys[$tablename][] = trim($createtable_lines[$i]);                         // set the constraint asside for later

        if ($first_constraint) {                                                                  // if it's the first constraint

          $first_constraint = false;
          $createtable_lines_without_triggers[$i - 1] = substr($createtable_lines_without_triggers[$i - 1], 0, -1); // remove the comma from the previous command in the CREATE TABLE script

        } else
          $this->foreign_keys[$tablename][count($this->foreign_keys[$tablename]) - 2] = substr($this->foreign_keys[$tablename][count($this->foreign_keys[$tablename]) - 2], 0, -1); // remove the comma from the previous constraint

      } else
        $createtable_lines_without_triggers[] = $createtable_lines[$i];                           // if the line does not contain a constraint command, store to "output" array

    }

    return implode($this->nl, $createtable_lines_without_triggers);                               // return the CREATE TABLE script sans foreign key constraints

  }


  // Return in SQL format any foreign key constraint commands that were setup asside earlier
  private function backup_foreign_key_constraints (): string {

    if (count($this->foreign_keys) > 0) {                                                         // if there are any foreign key constraints in this database

      // a header for this section
      $return  = $this->nl . '--' . $this->nl;
      $return .= '-- Foreign Key Constraints' . $this->nl;
      $return .= '--' . $this->nl . $this->nl;

      foreach($this->foreign_keys as $table => $constraints) {                                    // go through each table that has constraints

        $return .= '-- Table: ' . $table . $this->nl;                                             // label it

        for ($i = 0; $i < count($constraints); $i++)                                              // go through all constraints for this table
          $return .= 'ALTER TABLE `' . $table . '` ADD ' . $constraints[$i] . $this->default_delimiter . $this->nl;

        $return .= $this->nl;

      }

      return $return . $this->nl;

    }

    return '';                                                                                    // no constraints in this database

  }


  // create trigger creation string
  // return string back to parent
  private function backup_triggers_by_table (pdo &$db, &$table_list): string {

    // a header for this section
    $return  = $this->nl . '--' . $this->nl;
    $return .= '-- Triggers' . $this->nl;
    $return .= '--' . $this->nl . $this->nl;
    $return .= 'DELIMITER ' . $this->trigger_delimiter .  $this->nl . $this->nl;                  // if the delimiter is not changed, it trigger statements won't be handled correctly

    for ($table_idx = 0; $table_idx < count($table_list); $table_idx++) {                         // cycle through all of the tables

      $showtriggers_stmt = $db->query('SHOW TRIGGERS LIKE "' . $table_list[$table_idx] . '"');    // get triggers for this table
      $triggers = $showtriggers_stmt->fetchAll(PDO::FETCH_ASSOC);

      for ($i = 0; $i < count($triggers); $i++) {                                                 // cycle through all triggers for this table

        $return .= 'DROP TRIGGER IF EXISTS `' . $triggers[$i]['Trigger'] . '`' . $this->trigger_delimiter . $this->nl;
        $return .= 'CREATE DEFINER=`' . $triggers[$i]['Definer'] . '` TRIGGER `' . $triggers[$i]['Trigger'] . '` ' . $triggers[$i]['Timing'] . ' ' . $triggers[$i]['Event'] . ' ON `' . $triggers[$i]['Table'] . '` FOR EACH ROW ' . $this->nl;
        $return .= $triggers[$i]['Statement'] . $this->trigger_delimiter . $this->nl . $this->nl . $this->nl;

      }

    }

    $return .= 'DELIMITER ' . $this->default_delimiter . $this->nl . $this->nl;                   // change delimiter back to default

    return $return;

  }


  // write a new backup file, after it first checks if it should
  private function write_file (string $dbname, string &$return): bool {

    $dirname = $this->save_location . ($this->use_database_subdirectory ? $dbname . '/' : '');    // find directory location
    $db_archive_prefix = $dbname . '-backup-';                                                    // archive prefix -- date and extension is appended later
    $md5val = md5($return);                                                                       // generate MD5 to determine if the backup is different than the last backup (saves a file wrtie if it is)

    if (!is_dir($dirname))                                                                        // create directory if it does not exist
      mkdir($dirname, 0777, true);                                                                // 0777 mode is ignored on windows

    if (file_exists($dirname . $this->archive_info_filename))
      $lastmd5 = file($dirname . $this->archive_info_filename)[1];                                // get last backup's MD5

    else
      $lastmd5 = false;                                                                           // force file save if info file does not exist


    if ($lastmd5 != $md5val) {

      $filename = $dirname . $db_archive_prefix . date($this->date_format) . '.sql' . ($this->compress ? '.gz' : ''); // finalize file name
      $handle = fopen($filename, 'w+');                                                           // open backup file, automatically overwrite

      if ($this->compress)
        fwrite($handle, gzencode($return));                                                       // compress backup and write it

      else
        fwrite($handle, $return);                                                                 // write backup

      fclose($handle);                                                                            // close backup file

      // save info file with today's date
      $handle = fopen($dirname . $this->archive_info_filename, 'w+');                             // open info file
      fwrite($handle, date('Y m d') . $this->nl . $md5val);                                       // write info file
      fclose($handle);                                                                            // close info file

      return true;

    }

    return false;                                                                                 // file is the same as it was before

  }


}
