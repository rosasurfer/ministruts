

##### Behaviour for the last inserted row ID

| interface      | function                                         | previous queries | most recent query | reset between queries |
|----------------|--------------------------------------------------|:----------------:|:-----------------:|:---------------------:|
| MySQL          | `SELECT last_insert_id()`                        | yes              | yes               | no                    |
| php_mysql      | `mysql_insert_id()` <small><sup>1</sup></small>  | -                | yes               | yes                   |
| php_mysqli     | `mysqli_insert_id()` <small><sup>1</sup></small> | -                | yes               | yes                   |
| MySQLConnector | `MySQLConnector::lastInsertId()`                 |                  |                   |                       |
| PostgreSQL     | `SELECT lastval()` <small><sup>2</sup></small>   | yes              | yes               | no                    |
| PostgreSQL     | `INSERT INTO ... RETURNING ...`                  | -                | yes               | yes                   |
| php_pgsql      | -                                                |                  |                   |                       |
| SQLite         | `SELECT last_insert_rowid()`                     | yes              | yes               | no                    |
| php_sqlite3    | `SQLite3::lastInsertRowID()`                     | yes              | yes               | no                    |

<small><sup>1</sup></small> Instead of the last the currently inserted value is returned which is arguably useless.  
<small><sup>2</sup></small> since version 8.1

_ _ _

##### Behaviour for the number of affected rows

| interface   | function                                       | previous queries | most recent query | reset between queries |
|-------------|------------------------------------------------|:----------------:|:-----------------:|:---------------------:|
| MySQL       |                                                |                  |                   |                       |
| PostgreSQL  |                                                |                  |                   |                       |
| SQLite      |                                                |                  |                   |                       |
