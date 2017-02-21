
### Behaviour for the last inserted row ID

<table>
<tr>
    <th nowrap> Interface </th>
    <th nowrap> Function returns value for </th>
    <th nowrap> Previous queries </th>
    <th nowrap> Most recent query <small><sup>1</sup></small> </th>
</tr>

<tr>
    <td nowrap>                MySQL </td>
    <td nowrap>                <code>SELECT last_insert_id()</code> </td>
    <td nowrap align="center"> yes </td>
    <td nowrap align="center"> - </td>
</tr>
<tr>
    <td nowrap>                php_mysql </td>
    <td nowrap>                <code>mysql_insert_id()</code> </td>
    <td nowrap align="center"> - </td>
    <td nowrap align="center"> &nbsp; yes <small><sup>2</sup></small> </td>
</tr>
<tr>
    <td nowrap>                php_mysqli </td>
    <td nowrap>                <code>mysqli_insert_id()</code> </td>
    <td nowrap align="center"> - </td>
    <td nowrap align="center"> &nbsp; yes <small><sup>3</sup></small> </td>
</tr>
<tr>
    <td nowrap>                rosasurfer\db\mysql\MySQLConnector </td>
    <td nowrap>                <code>MySQLConnector::lastInsertId()</code><br><code>MySQLResult::lastInsertId()</code> </td>
    <td nowrap align="center"> &nbsp; yes <small><sup>5</sup></small> </td>
    <td nowrap align="center"> - </td>
</tr>
<tr>
    <td nowrap>                PostgreSQL </td>
    <td nowrap>                <code>INSERT INTO ... RETURNING ...</code><br><code>SELECT lastval()</code> <small><sup>6</sup></small> </td>
    <td nowrap align="center"> -<br>yes </td>
    <td nowrap align="center"> yes<br>- </td>
</tr>
<tr>
    <td nowrap>                php_pgsql </td>
    <td nowrap>                &nbsp;not supported </td>
    <td nowrap align="center"> &nbsp; </td>
    <td nowrap align="center"> &nbsp; </td>
</tr>
<tr>
    <td nowrap>                rosasurfer\db\pgsql\PostgresConnector </td>
    <td nowrap>                <code>PostgresConnector::lastInsertId()</code><br><code>PostgresResult::lastInsertId()</code> </td>
    <td nowrap align="center"> &nbsp; yes <small><sup>4</sup></small> </td>
    <td nowrap align="center"> &nbsp; </td>
</tr>
<tr>
    <td nowrap>                SQLite </td>
    <td nowrap>                <code>SELECT last_insert_rowid()</code> </td>
    <td nowrap align="center"> yes </td>
    <td nowrap align="center"> - </td>
</tr>
<tr>
    <td nowrap>                php_sqlite3 </td>
    <td nowrap>                <code>SQLite3::lastInsertRowID()</code> </td>
    <td nowrap align="center"> yes </td>
    <td nowrap align="center"> - </td>
</tr>
<tr>
    <td nowrap>                rosasurfer\db\sqlite\SQLiteConnector </td>
    <td nowrap>                <code>SQLiteConnector::lastInsertId()</code><br><code>SQLiteResult::lastInsertId()</code> </td>
    <td nowrap align="center"> &nbsp; yes <small><sup>5</sup></small> </td>
    <td nowrap align="center"> - </td>
</tr>
</table>

<small><sup>1</sup></small> &nbsp; The value is reset between queries.
<small><sup>2</sup></small> &nbsp; Returns the first inserted row ID. `lastInsertId = mysql_insert_id() + mysql_affected_rows() - 1`.
<small><sup>3</sup></small> &nbsp; Returns the first inserted row ID. `lastInsertId = mysqli_insert_id() + mysqli_affected_rows() - 1`.
<small><sup>4</sup></small> &nbsp;`Result::lastInsertId()` returns the value of `Connector::lastInsertId()`.
<small><sup>5</sup></small> &nbsp;`Result::lastInsertId()` returns the value of `Connector::lastInsertId()` at result creation time.
<small><sup>6</sup></small> &nbsp; Since PostgreSQL 8.1. Raises SQLSTATE 55000 if no row was yet inserted in the current session.

_ _ _

### Behaviour for the number of affected rows

<table>
<tr>
    <th nowrap> Interface </th>
    <th nowrap> Function returns value for </th>
    <th nowrap> Previous queries </th>
    <th nowrap> Most recent query <small><sup>1</sup></small> </th>
</tr>

<tr>
    <td nowrap>                MySQL </td>
    <td nowrap>                <code>SELECT row_count()</code> </td>
    <td nowrap align="center"> - </td>
    <td nowrap align="center"> &nbsp;&nbsp;&nbsp; yes <small><sup>2 8</sup></small> </td>
</tr>
<tr>
    <td nowrap>                php_mysql </td>
    <td nowrap>                <code>mysql_affected_rows()</code> </td>
    <td nowrap align="center"> - </td>
    <td nowrap align="center"> &nbsp;&nbsp;&nbsp; yes <small><sup>3 8</sup></small> </td>
</tr>
<tr>
    <td nowrap>                rosasurfer\db\mysql\MySQLConnector </td>
    <td nowrap>                <code>MySQLConnector::lastAffectedRows()</code><br><code>MySQLResult::lastAffectedRows()</code> </td>
    <td nowrap align="center"> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; yes <small><sup>4 8 9</sup></small> </td>
    <td nowrap align="center"> - </td>
</tr>
<tr>
    <td nowrap>                PostgreSQL </td>
    <td nowrap>                <code>INSERT&brvbar;UPDATE&brvbar;DELETE &#46;.. RETURNING &#46;..</code> </td>
    <td nowrap align="center"> &nbsp; yes <small><sup>2</sup></small> </td>
    <td nowrap align="center"> &nbsp; </td>
</tr>
<tr>
    <td nowrap>                php_pgsql </td>
    <td nowrap>                <code>pg_affected_rows()</code> </td>
    <td nowrap align="center"> - </td>
    <td nowrap align="center"> &nbsp;&nbsp;&nbsp; yes <small><sup>5 6</sup></small> </td>
</tr>
<tr>
    <td nowrap>                rosasurfer\db\pgsql\PostgresConnector </td>
    <td nowrap>                <code>PostgresConnector::lastAffectedRows()</code><br><code>PostgresResult::lastAffectedRows()</code> </td>
    <td nowrap align="center"> &nbsp;&nbsp;&nbsp; yes <small><sup>5 9</sup></small> </td>
    <td nowrap align="center"> &nbsp; </td>
</tr>
<tr>
    <td nowrap>                SQLite </td>
    <td nowrap>                <code>SELECT changes()</code><br><code>SELECT total_changes()</code> </td>
    <td nowrap align="center"> &nbsp; yes <small><sup>5</sup></small><br>&nbsp; yes <small><sup>7</sup></small> </td>
    <td nowrap align="center"> - </td>
</tr>
<tr>
    <td nowrap>                php_sqlite3 </td>
    <td nowrap>                <code>SQLite3::changes()</code> </td>
    <td nowrap align="center"> &nbsp; yes <small><sup>5</sup></small> </td>
    <td nowrap align="center"> - </td>
</tr>
<tr>
    <td nowrap>                rosasurfer\db\sqlite\SQLiteConnector </td>
    <td nowrap>                <code>SQLiteConnector::lastAffectedRows()</code><br><code>SQLiteResult::lastAffectedRows()</code> </td>
    <td nowrap align="center"> &nbsp;&nbsp;&nbsp; yes <small><sup>5 9</sup></small> </td>
    <td nowrap align="center"> - </td>
</tr>
</table>

<small><sup>1</sup></small> &nbsp; The value is reset between queries.
<small><sup>2</sup></small> &nbsp; Rows modified by the most recent query if it was an `INSERT`, `UPDATE` or `DELETE` query.
<small><sup>3</sup></small> &nbsp; Same as <small><sup>2</sup></small>, additionally for queries returning rows the number of returned rows.
<small><sup>4</sup></small> &nbsp; Rows modified by the last `INSERT`, `UPDATE` or `DELETE` query.
<small><sup>5</sup></small> &nbsp; Rows matched by the last `INSERT`, `UPDATE` or `DELETE` query.
<small><sup>6</sup></small> &nbsp; Same as <small><sup>5</sup></small>, additionally for `SELECT` queries the number of returned rows.
<small><sup>7</sup></small> &nbsp; Rows matched by all `INSERT`, `UPDATE` or `DELETE` queries since session start.
<small><sup>8</sup></small> &nbsp; Since MySQL 5.5.5 also for `ALTER TABLE` and `LOAD DATA INFILE` queries.
<small><sup>9</sup></small> &nbsp;`Result::lastAffectedRows()` returns the value of `Connector::lastAffectedRows()` at result creation time.
