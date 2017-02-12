

#### Behaviour for the last inserted row ID

| Interface                            | Function returns value for                                                        | Previous queries                | Most recent query <small><sup>1</sup></small> |
|--------------------------------------|-----------------------------------------------------------------------------------|:-------------------------------:|:---------------------------------------------:|
| MySQL                                | `SELECT last_insert_id()`                                                         | yes                             | -                                             |
| php_mysql                            | `mysql_insert_id()`                                                               | -                               | yes <small><sup>2</sup></small>               |
| php_mysqli                           | `mysqli_insert_id()`                                                              | -                               | yes <small><sup>3</sup></small>               |
| rosasurfer\db\mysql\MySQLConnector   | `MySQLConnector::lastInsertId()`<br>`MySQLResult::lastInsertId()`                 | yes <small><sup>4</sup></small> | -                                             |
| PostgreSQL                           | `INSERT INTO ... RETURNING ...`<br>`SELECT lastval()` <small><sup>5</sup></small> | -<br>yes                        | yes<br>-                                      |
| php_pgsql                            | &nbsp;not supported                                                               |                                 |                                               |
| SQLite                               | `SELECT last_insert_rowid()`                                                      | yes                             | -                                             |
| php_sqlite3                          | `SQLite3::lastInsertRowID()`                                                      | yes                             | -                                             |
| rosasurfer\db\sqlite\SQLiteConnector | `SQLiteConnector::lastInsertId()`<br>`SQLiteResult::lastInsertId()`               | yes <small><sup>4</sup></small> | -                                             |

<small><sup>1</sup></small> &nbsp;&nbsp;The value is reset between queries.  
<small><sup>2</sup></small> &nbsp;&nbsp;Returns the first inserted row ID. `lastInsertId = mysql_insert_id() + mysql_affected_rows() - 1`.  
<small><sup>3</sup></small> &nbsp;&nbsp;Returns the first inserted row ID. `lastInsertId = mysqli_insert_id() + mysqli_affected_rows() - 1`.  
<small><sup>4</sup></small> `Result::lastInsertId()` returns the value of `Connector::lastInsertId()` at instance creation time.  
<small><sup>5</sup></small> &nbsp;&nbsp;since PostgreSQL 8.1  

_ _ _

#### Behaviour for the number of affected rows

| Interface                          | Function returns value for                                        | Previous queries                                                   | Most recent query <small><sup>1</sup></small> |
|------------------------------------|-------------------------------------------------------------------|:------------------------------------------------------------------:|:---------------------------------------------:|
| MySQL                              | `SELECT row_count()`                                              | -                                                                  | yes <small><sup>2</sup></small>               |
| php_mysql                          | `mysql_affected_rows()`                                           | -                                                                  | yes <small><sup>3</sup></small>               |
| rosasurfer\db\mysql\MySQLConnector | `MySQLConnector::affectedRows()`<br>`MySQLResult::affectedRows()` |                                                                    |                                               |
| PostgreSQL                         |                                                                   |                                                                    |                                               |
| SQLite                             | `SELECT changes()`<br>`SELECT total_changes()`                    | yes <small><sup>4</sup></small><br>yes <small><sup>5</sup></small> | -                                             |
| php_sqlite3                        | `SQLite3::changes()`                                              | yes <small><sup>4</sup></small>                                    | -                                             |

<small><sup>1</sup></small> &nbsp;&nbsp;The value is reset between queries.  
<small><sup>2</sup></small> &nbsp;&nbsp;Rows modified by the most recent query if it was an INSERT, DELETE or UPDATE. Since MySQL 5.5.5 also for ALTER TABLE and LOAD DATA INFILE.  
<small><sup>3</sup></small> &nbsp;&nbsp;Like <small><sup>2</sup></small>, additionally for queries returning rows the number of returned rows.  
<small><sup>4</sup></small> &nbsp;&nbsp;Rows modified by the last INSERT, DELETE or UPDATE.  
<small><sup>5</sup></small> &nbsp;&nbsp;Rows modified by all INSERT, DELETE or UPDATE statements since connection start.  
