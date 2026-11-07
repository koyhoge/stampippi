# 💮 Stampippi

**Stampippi** は、個人や運営グループ（Group）が自由に Web 上で GPS スタンプラリーを作成・公開し、参加者がスマートフォン等からチェックインしてスタンプを集められるプラットフォームです。

---

## 主な特徴・ドメインモデル

### 1. 権限ロール ＆ 3階層構造（Group ＋ シリーズ ＋ ラリー）
- **権限ロール**:
  - **管理者 (`is_admin = true`)**: グループ (`Group`)・ラリーシリーズ (`RallySeries`)・スタンプラリー (`StampRally`) の新規作成および全管理が可能。管理画面 (`/my`) または `php artisan user:admin @handle` で他ユーザーを管理者に指定できます。
  - **グループメンバー (`Group` 所属ユーザー)**: 所属グループのラリーシリーズに属するスタンプラリー (`StampRally`) とベニュー (`StampLocation`) の作成・編集が可能。
  - **一般ユーザー**: スタンプラリーへの参加・チェックイン・達成証の閲覧（マイページ `/dashboard`）が可能（ラリーの新規作成は不可）。
- **Group（運営グループ）**: 複数の登録済みユーザー（`@handle` またはメールアドレスで追加）が所属し、シリーズやスタンプラリーを共同管理・引き継ぎできます。
- **ラリーシリーズ (`RallySeries`)**: 年次開催のイベントや複数コースなど、複数のスタンプラリーを束ねる上位グループです。
- **スタンプラリー (`StampRally`)**: 1つの開催企画単位。複数のスタンプロケーションを持ちます。
- **スタンプロケーション (`StampLocation`)**: 実際に1回のスタンプを押すベニュー（会場・地点）。緯度・経度と判定半径（メートル）を持ちます。

### 2. 柔軟な期間設定（イベント開催 ＆ 常設対応）
- スタンプラリー全体の開催期間（`starts_at` / `ends_at`）および各ベニューの打刻可能期間（`available_from` / `available_until`）は任意項目です。
- 日時を指定すれば「イベント開催日のみ押せるベニュー」になり、空欄にすれば「常設（無期限でいつでも押せる）ラリー／ベニュー」として動作します。

### 3. GPS距離判定 ＆ ローカル開発用シミュレーター
- サーバー側で `mjaschen/phpgeo`（Haversine 公式）を用いて現在地とベニューの距離を正確に計算し、指定された判定半径以内であればスタンプを獲得できます。
- `APP_DEV_TOOLS=true`（`config/app.php` の `'dev_tools'`）を有効にすると、スタンプラリー画面に **「開発用 GPSシミュレーター」** およびログイン画面に **「開発用 ワンクリックログイン」** が表示されます。不要な場合や本番環境では `APP_DEV_TOOLS=false`（デフォルト `false`）で完全にオフになります。

---

## 技術スタック

- **言語 / フレームワーク**: PHP 8.2+ (8.5 対応) / Laravel 12
- **データベース**:
  - ローカル開発: SQLite（設定不要ですぐ動作）または PostgreSQL
  - 本番想定: Neon (Serverless PostgreSQL) 等（`.env` の切り替えのみで対応）
- **地図表示**: Leaflet.js + OpenStreetMap（APIキー不要）
- **位置距離計算**: `mjaschen/phpgeo`
- **認証**: Laravel Socialite (Google OAuth 2.0) ＋ `@handle` / メールアドレス認証

---

## ローカル開発環境のセットアップ

### 1. 依存パッケージのインストール

```bash
composer install
```

### 2. 環境設定ファイルとデータベースの準備

```bash
cp .env.example .env
php artisan key:generate
php artisan storage:link
touch database/database.sqlite
php artisan migrate:fresh --seed
```

### 3. 開発サーバーの起動

```bash
php artisan serve
```

ブラウザで **http://localhost:8000** にアクセスしてください。

---

## 開発用ログイン機能

`php artisan migrate:fresh --seed` を実行すると、動作確認用のサンプルデータ（グループ、ラリーシリーズ、期間指定ラリー、常設ラリー）とテストユーザーが投入されます。
ローカル環境（`APP_ENV=local`）では、ログイン画面（http://localhost:8000/login）の **「ローカル開発用 ワンクリックログイン」** からパスワード入力なしで各ロール（グループオーナー、運営メンバー、一般参加者）の動作を確認できます。

---

## テストの実行

```bash
php artisan test
```

---

## 本番環境（Neon / Cloud Run / Laravel Cloud）への移行設定

本番デプロイ時は `.env`（または環境の Secret / 環境変数）で以下を設定してください。

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_DEV_TOOLS=false
APP_URL=https://your-domain.example.com

# Neon (Serverless PostgreSQL) 接続例
DB_CONNECTION=pgsql
DB_HOST=ep-xxxx.ap-southeast-1.aws.neon.tech
DB_PORT=5432
DB_DATABASE=stampippi
DB_USERNAME=neondb_owner
DB_PASSWORD=your_password
DB_SSLMODE=require

# Google OAuth 認証設定
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"

# スタンプ画像ストレージ ドライバ設定 (public / laravel_cloud / s3 / database または独自プラグイン)
# 1) Laravel Cloud の Object Storage を使う場合:
#    LARAVEL_CLOUD_DISK_CONFIG が存在する場合は STAMP_IMAGE_DRIVER 未指定でも自動で選択されます。
#    明示指定する場合は以下を設定:
STAMP_IMAGE_DRIVER=laravel_cloud
# STAMP_IMAGE_CLOUD_DISK=my-object-storage-disk

# 2) AWS S3 / S3互換外部ストレージ (Cloudflare R2 / GCS) を使う場合:
# STAMP_IMAGE_DRIVER=s3
# STAMP_IMAGE_PUBLIC_URL=https://cdn.your-domain.example.com
# AWS_ACCESS_KEY_ID=your_access_key
# AWS_SECRET_ACCESS_KEY=your_secret_key
# AWS_DEFAULT_REGION=ap-northeast-1
# AWS_BUCKET=stampippi-assets

# 3) 外部バケットを使わず DB (Neon Postgres等) にインライン保存する場合:
# STAMP_IMAGE_DRIVER=database
```

---

## ライセンス

[MIT License](LICENSE)
