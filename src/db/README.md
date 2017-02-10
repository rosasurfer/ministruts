
#### Last inserted row ID

| interface   | function                                     | recent query | current query | reset between queries |
|-------------|----------------------------------------------|--------------|---------------|-----------------------|
| MySQL       | SELECT last_insert_id()                      | yes          | -             | no                    |
| php_mysql   | mysql_insert_id()                            | -            | yes           | yes                   |
| PostgreSQL  | SELECT lastval() <small><sup>1</sup></small> | yes          | -             | no                    |
| PostgreSQL  | INSERT INTO ... RETURNING ...                | -            | yes           | yes                   |
| php_pgsql   | -                                            |              |               |                       |
| SQLite      | SELECT last_insert_rowid()                   | yes          | -             | no                    |
| php_sqlite3 | SQLite3::lastInsertRowID()                   | yes          | -             | no                    |

<small>1</small> - since version 8.1
