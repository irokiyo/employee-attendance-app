# 勤怠アプリ
本アプリは、ユーザーの出退勤・休憩・勤怠修正申請を管理する勤怠管理アプリです。  
一般ユーザーと管理者で機能が分かれており、管理者は申請の承認・修正が可能です。  

## 主な機能

- 出勤 / 退勤打刻
- 休憩開始 / 終了
- 勤怠修正申請
- 管理者による承認 / 却下

## 環境構築

### Docker ビルド
1. git clone https://github.com/irokiyo/employee-attendance-app.git attendance-clone  
1. cd attendance-clone  
1. docker-compose up -d --build  

### Laravel 環境構築

1. docker-compose exec php bash  
1. composer install  
1. cp .env.example .env  
1. .env ファイルの一部を以下のように編集
```
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass
```
6. docker-compose exec php bash  
1. php artisan key:generate  
1. php artisan migrate:fresh  
1. php artisan db:seed  
### テスト用データベース作成
テスト実行にはテスト用データベースが必要になるため以下のデータベースを作成してください  
```sql
CREATE DATABASE laravel_test_db;
```
1. php artisan test  

## メール認証(MailHog)
メール認証はMailHogを使用しています  

### MailHog 環境構築  

1. .envに以下を追加する
```
MAIL_MAILER=smtp  
MAIL_HOST=mailhog  
MAIL_PORT=1025  
MAIL_USERNAME=null  
MAIL_PASSWORD=null  
MAIL_ENCRYPTION=null  
MAIL_FROM_ADDRESS=test@example.com  
MAIL_FROM_NAME="${APP_NAME}"  
```
1. docker-compose down
1. docker-compose up -d
1. docker-compose exec php bash
1. php artisan config:clear


## ログイン用初期データ  
### 一般ユーザー
- メールアドレス: reina.n@coachtech.com  
- パスワード: password  
### 管理者
- メールアドレス: norio.n@coachtech.com  
- パスワード: password  

## 使用技術
- MySQL 8.0.26  
- Laravel: 8.83.3  
- PHP 8.1 (Docker)  
- MailHog (ローカル開発用)  

## テスト・品質管理
- PHPUnit（Feature Test）
- PHPStan（静的解析）
- PHPCS（コーディング規約チェック）
- GitHub Actions（CI）

## URL
- 環境開発: http://localhost/  
- phpMyAdmin: http://localhost:8080/  
- MailHog: http://localhost:8025/  

## ER 図
![ER図](attendance.drawio.png)  

## テーブル

usersテーブル
| カラム名   |  型              |primary key  |unique key  |not null  |foreign key  |
| --------- | ----------------|-------------|------------|----------|-------------|
| id        | bigint unsigned |⚪︎           |             |⚪︎       　|             |
|                出勤のない日の勤怠詳細画面（管理者）     | /admin/attendance/{user}/date/{date}|

