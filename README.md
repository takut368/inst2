```md
# README

以下のリポジトリ（またはディレクトリ）には、Instagram風のストーリー閲覧・管理機能を実装したウェブアプリケーションが含まれています。管理者は `admin.php` からアカウントやストーリーを作成・編集・削除でき、一般ユーザーは URL アクセスのみでログイン不要でストーリーを閲覧できます。

## ディレクトリ構成

```
instagran.azurewebsites.net/
├── admin.php        // 管理画面。アカウント作成やストーリー管理などの主要機能を提供
├── index.php        // ストーリー閲覧用。htaccess の RewriteRule でパラメーターを受け取る
├── account.json     // 管理者アカウントやストーリー情報を保存
├── system.json      // システム管理者やレベル設定、管理者数上限などを保存
├── data/
│   ├── ABC123/
│   │   └── data.json  // 特定アカウント(ABC123)のストーリー閲覧数やリンク遷移数を記録
│   ├── DEF456/
│   │   └── data.json
│   └── ...
├── uploads/
│   ├── icons/        // 各アカウントアイコン画像を保存
│   └── stories/      // ストーリー画像や動画を保存
│       ├── ABC123/
│       │   ├── story_1.png
│       │   ├── story_2.mp4
│       │   └── ...
│       └── ...
├── .htaccess         // RewriteRule設定で、https://instagran.azurewebsites.net/XXXXXX => index.php?account=XXXXXX
└── ...
```

## セットアップ

1. **サーバーへの配置**  
   本フォルダを Apache サーバーなどに配置し、`.htaccess` の RewriteRule が有効となるよう設定してください。  
   HTTPS 通信が推奨です。

2. **パーミッション**  
   - `account.json` や `system.json` は書き込み可能なパーミッションに設定してください。  
   - `data/` および `uploads/` 以下のディレクトリにもファイルを書き込むために適切なパーミッションを付与してください。

3. **初期アカウント**  
   - `admin.php` にアクセスし、デフォルト管理者 (`admin` / `admin`) でログインしてください。  
   - 初回ログイン時にパスワード変更が要求されます（`pw_changed` が `false` の状態）。

4. **使用方法**  
   - 管理者は `admin.php` にログインし、アカウント作成・ストーリー追加などを行います。  
   - 一般ユーザーは `https://instagran.azurewebsites.net/XXXXXX` 形式のURLにアクセスし、ストーリーを閲覧します。

## 管理画面 (admin.php)

- **アカウント作成**  
  アカウント名とアイコン画像を指定し、アカウントを新規作成します。  
- **ストーリー作成**  
  画像または動画をアップロードし、テキストやリンク先URLを設定。  
  画像・動画はサーバー側で9:16比率に自動加工（設定に応じて）。  
- **ストーリー編集/削除**  
  作成済みストーリーを修正または削除できます。  
- **アクセス解析 (stats)**  
  メニューから `?stats=1` を開くことで、ストーリーの閲覧数やリンク遷移数を一覧表示できます。

## ストーリー閲覧 (index.php)

- `.htaccess` の RewriteRule で `/XXXXXX` にアクセスすると `index.php?account=XXXXXX` へ転送  
- 10秒間のプログレスバーを上部に表示し、自動でストーリーが切り替わる  
- 右上にバツボタンを表示して閉じられるようにし、左上にはアカウントアイコンとIDを表示  
- 動画にも対応し、静止画同様にフルスクリーンで表示  
- ストーリー閲覧数を `access_count` でカウントし、リンクタップ時に `redirect_count` も記録してアクセス解析を行う

## 注意事項

- **セキュリティ**  
  - 試用版のため ID/PW をハッシュ化せずに保存している部分があります。  
  - 運用時はHTTPS化、パスワード管理に十分注意してください。  
  - ファイルアップロードを行うため、拡張子とファイルサイズの制限、およびマルウェア対策を検討してください。

- **バックアップ**  
  - `account.json`, `system.json`, `data/`, `uploads/` フォルダは定期的にバックアップを取ることを推奨します。

- **拡張**  
  - 時間別・日別のアクセス集計や、ストーリーの予約投稿なども拡張しやすい構造です。  
  - 必要に応じてソースコードを変更し、Gitなどでバージョン管理すると運用がスムーズです。

以上がこのストーリーサイトのディレクトリ構成と概要です。  
運用時はサーバーのログやアクセス状況をモニタリングし、安全かつ快適にユーザーがストーリーを閲覧できるよう管理をお願いいたします。
```
