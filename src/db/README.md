
### Behavior for the last inserted row ID

| Interface                             | Function returns value for                                                        | Previous queries                | Most recent query <small><sup>1</sup></small> |
|---------------------------------------|-----------------------------------------------------------------------------------|:-------------------------------:|:---------------------------------------------:|
| MySQL                                 | `SELECT last_insert_id()`                                                         | yes                             | -                                             |
| php_mysql                             | `mysql_insert_id()`                                                               | -                               | yes <small><sup>2</sup></small>               |
| php_mysqli                            | `mysqli_insert_id()`                                                              | -                               | yes <small><sup>3</sup></small>               |
| rosasurfer\db\mysql\MySQLConnector    | `MySQLConnector::lastInsertId()`<br>`MySQLResult::lastInsertId()`                 | yes <small><sup>4</sup></small> | -                                             |
| PostgreSQL                            | `INSERT INTO ... RETURNING ...`<br>`SELECT lastval()` <small><sup>5</sup></small> | -<br>yes                        | yes<br>-                                      |
| php_pgsql                             | &nbsp;not supported                                                               |                                 |                                               |
| rosasurfer\db\pgsql\PostgresConnector | `PostgresConnector::lastInsertId()`<br>`PostgresResult::lastInsertId()`           |                                 |                                               |
| SQLite                                | `SELECT last_insert_rowid()`                                                      | yes                             | -                                             |
| php_sqlite3                           | `SQLite3::lastInsertRowID()`                                                      | yes                             | -                                             |
| rosasurfer\db\sqlite\SQLiteConnector  | `SQLiteConnector::lastInsertId()`<br>`SQLiteResult::lastInsertId()`               | yes <small><sup>4</sup></small> | -                                             |

<small><sup>1</sup></small> &nbsp; The value is reset between queries.  
<small><sup>2</sup></small> &nbsp; Returns the first inserted row ID. `lastInsertId = mysql_insert_id() + mysql_affected_rows() - 1`.  
<small><sup>3</sup></small> &nbsp; Returns the first inserted row ID. `lastInsertId = mysqli_insert_id() + mysqli_affected_rows() - 1`.  
<small><sup>4</sup></small> &nbsp;`Result::lastInsertId()` returns the value of `Connector::lastInsertId()` at result creation time.  
<small><sup>5</sup></small> &nbsp; Since PostgreSQL 8.1, will raise SQL error 55000 until the first row is inserted.  

_ _ _

### Behavior for the number of affected rows

| Interface                             | Function returns value for                                                      | Previous queries                                                   | Most recent query <small><sup>1</sup></small> |
|---------------------------------------|---------------------------------------------------------------------------------|:------------------------------------------------------------------:|:---------------------------------------------:|
| MySQL                                 | `SELECT row_count()`                                                            | -                                                                  | yes <small><sup>2</sup></small>               |
| php_mysql                             | `mysql_affected_rows()`                                                         | -                                                                  | yes <small><sup>3</sup></small>               |
| rosasurfer\db\mysql\MySQLConnector    | `MySQLConnector::lastAffectedRows()`<br>`MySQLResult::lastAffectedRows()`       | &nbsp;&nbsp;&nbsp; yes <small><sup>4 &nbsp;7</sup></small>         | -                                             |
| PostgreSQL                            |                                                                                 |                                                                    |                                               |
| rosasurfer\db\pgsql\PostgresConnector | `PostgresConnector::lastAffectedRows()`<br>`PostgresResult::lastAffectedRows()` |                                                                    |                                               |
| SQLite                                | `SELECT changes()`<br>`SELECT total_changes()`                                  | yes <small><sup>5</sup></small><br>yes <small><sup>6</sup></small> | -                                             |
| php_sqlite3                           | `SQLite3::changes()`                                                            | yes <small><sup>5</sup></small>                                    | -                                             |
| rosasurfer\db\sqlite\SQLiteConnector  | `SQLiteConnector::lastAffectedRows()`<br>`SQLiteResult::lastAffectedRows()`     | &nbsp;&nbsp;&nbsp; yes <small><sup>5 &nbsp;7</sup></small>         | -                                             |

<small><sup>1</sup></small> &nbsp; The value is reset between queries.  
<small><sup>2</sup></small> &nbsp; Rows modified by the most recent query if it was an `INSERT`, `UPDATE` or `DELETE` query. Since MySQL 5.5.5 also for `ALTER TABLE` and  
                            &nbsp;&nbsp; `LOAD DATA INFILE` queries.  
<small><sup>3</sup></small> &nbsp; Same as <small><sup>2</sup></small>, additionally for queries returning rows (`SELECT` etc.)
                            the number of returned rows.  
<small><sup>4</sup></small> &nbsp; Rows modified by the last `INSERT`, `UPDATE` or `DELETE` query. Since MySQL 5.5.5 also for
                            `ALTER TABLE` and `LOAD DATA INFILE` queries.  
<small><sup>5</sup></small> &nbsp; Rows matched by the last `INSERT`, `UPDATE` or `DELETE` query.  
<small><sup>6</sup></small> &nbsp; Rows matched by all `INSERT`, `UPDATE` or `DELETE` queries since session start.  
<small><sup>7</sup></small> &nbsp;`Result::lastAffectedRows()` returns the value of `Connector::lastAffectedRows()` at result creation time.  
