# DirectAdmin deployment

Tiba Quip is standalone. It does not need WordPress, JD Shop, or any migration source.

1. Create a MySQL database and database user in DirectAdmin, and grant the user access to the database.
2. Import `storage/native-backup-20260908-083651.sql.gz` with phpMyAdmin or DirectAdmin's database importer.
3. Upload all project files and the complete `uploads/` folder. Preserve all `.htaccess` files.
4. Update `control/core/database/connect.php` with the DirectAdmin database host, database name, database user and password. DirectAdmin usually prefixes database and user names with the account name.
5. Set `environment` to `production`. The application detects its domain and installation folder automatically.
6. Make `uploads/media/` and `storage/` writable by PHP.

PHP 7.4+ is supported, with mysqli/mysqlnd, mbstring, DOM and fileinfo enabled. PHP 8.1+ is recommended.

## If DirectAdmin shows HTTP 500

1. In DirectAdmin, select PHP 8.1 or newer for this domain. The application also provides a clear message for PHP versions below 7.4.
2. Enable `mysqli`, `mysqlnd`, `mbstring`, `dom` and `fileinfo` in the selected PHP version.
3. Confirm that `control/core/database/connect.php` has the DirectAdmin database name, user and password, then confirm the native backup was imported into that same database.
4. Make sure `index.php`, `app/`, `assets/`, `overall/`, `control/`, `uploads/` and every `.htaccess` file were uploaded into the domain document root.
5. Set folders to 755 and make `storage/` and `uploads/media/` writable by the PHP user, usually 775 on DirectAdmin.
6. Open DirectAdmin's **Site Summary / Statistics / Logs** error log after one request. A database failure is recorded there without exposing credentials publicly.
