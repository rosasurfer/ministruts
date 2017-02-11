

##### Behaviour for the last inserted row ID

| Interface      | Function                                                                          | All previous queries            | Only most recent query <small><sup>1</sup></small> |
|----------------|-----------------------------------------------------------------------------------|:-------------------------------:|:--------------------------------------------------:|
| MySQL          | `SELECT last_insert_id()`                                                         | yes                             | -                                                  |
| php_mysql      | `mysql_insert_id()` <small><sup>2</sup></small>                                   | -                               | yes                                                |
| php_mysqli     | `mysqli_insert_id()` <small><sup>2</sup></small>                                  | -                               | yes                                                |
| MySQLConnector | `MySQLConnector::lastInsertId()`<br>`MySQLResult::lastInsertId()`                 | yes <small><sup>3</sup></small> | -                                                  |
| PostgreSQL     | `INSERT INTO ... RETURNING ...`<br>`SELECT lastval()` <small><sup>4</sup></small> | -<br>yes                        | yes<br>-                                           |
| php_pgsql      | &nbsp;&nbsp;not supported                                                         |                                 |                                                    |
| SQLite         | `SELECT last_insert_rowid()`                                                      | yes                             | -                                                  |
| php_sqlite3    | `SQLite3::lastInsertRowID()`                                                      | yes                             | -                                                  |

<small><sup>1</sup></small> The value is reset between queries.  
<small><sup>2</sup></small> Returns the ID of the currently first inserted row. `mysql_affected_rows()` returns the number of inserted rows.  
<small><sup>3</sup></small> `MySQLResult::lastInsertId()` returns the value of `MySQLConnector::lastInsertId()` at `MySQLResult`
                            creation time (think of multiple results).  
<small><sup>4</sup></small> since version 8.1

_ _ _

##### Behaviour for the number of affected rows

| interface   | function                                       | previous queries | most recent query | reset between queries |
|-------------|------------------------------------------------|:----------------:|:-----------------:|:---------------------:|
| MySQL       |                                                |                  |                   |                       |
| PostgreSQL  |                                                |                  |                   |                       |
| SQLite      |                                                |                  |                   |                       |
