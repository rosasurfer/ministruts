
#### Last inserted ID

| database   | function                        | recent query | last query | reset between queries |
|------------|---------------------------------|--------------|------------|-----------------------|
| MySQL      | php: mysql_insert_id()          | -            | yes        | yes                   |
| MySQL      | sql: last_insert_id()           | yes          | -          | no                    |
| PostgreSQL | sql: insert into ... return ... | -            | yes        | yes                   |
| PostgreSQL | sql: lastval()                  | yes          | -          | no                    |
| SQLite3    | php: SQLite3::lastInsertRowID() | yes          | -          | no                    |
