-- MySQL を使う結合テスト（tests/Integration）専用の DB。
-- 開発用 DB（tasuku）とは分け、テストで中身が消えても困らないようにする。
-- このスクリプトは db-data ボリュームが空のとき（初回起動時）にだけ実行される。
CREATE DATABASE IF NOT EXISTS `tasuku_testing` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON `tasuku_testing`.* TO 'tasuku'@'%';
FLUSH PRIVILEGES;
