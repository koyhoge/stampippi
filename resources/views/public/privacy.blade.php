@extends('layouts.app')

@section('title', 'プライバシーポリシー - ' . config('app.name', 'Stampippi'))

@section('content')
<div class="max-w-4xl mx-auto py-4 sm:py-8 space-y-6">
    <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-10 shadow-sm space-y-8">
        <!-- Header -->
        <div class="border-b border-slate-200 pb-6">
            <div class="flex items-center gap-3 mb-2">
                <span class="text-3xl">🔒</span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900">プライバシーポリシー</h1>
            </div>
            <p class="text-sm text-slate-600 leading-relaxed mt-2">
                Stampippi（以下「本サービス」といいます）は、利用者の皆様に安心してGPSスタンプラリーをお楽しみいただけるよう、プライバシーの保護に最大限の配慮を行っています。本プライバシーポリシーでは、本サービスにおける個人情報および利用者情報の取り扱い方針について説明します。
            </p>
        </div>

        <!-- Section 1 -->
        <section class="space-y-3">
            <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                <span class="text-indigo-600 font-mono text-base">01.</span>
                <span>取得する情報</span>
            </h2>
            <p class="text-sm text-slate-600 leading-relaxed">
                本サービスでは、円滑なサービス提供のために以下の情報を取得することがあります。
            </p>
            <div class="grid gap-3 pt-1">
                <div class="bg-slate-50 border border-slate-100 rounded-xl p-4 space-y-1.5">
                    <h3 class="text-xs font-bold text-slate-800">👤 アカウント情報</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        外部サービス連携（Google、GitHub 等の OAuth 認証）を利用したアカウント登録・ログイン時に、氏名・ニックネーム、メールアドレス、プロフィール画像 URL、外部サービス固有のアカウント識別子を取得します。また、利用者が任意で設定したプロフィール画像や表示名も含まれます。
                    </p>
                </div>
                <div class="bg-slate-50 border border-slate-100 rounded-xl p-4 space-y-1.5">
                    <h3 class="text-xs font-bold text-slate-800">📍 位置情報（GPS）</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        ベニュー（スタンプロケーション）へのチェックイン（スタンプ獲得判定）を行う際に、利用者の同意のもと端末のブラウザから一時的に緯度・経度情報を取得します。<strong class="text-slate-800">チェックイン時における距離判定にのみ使用し、利用者の常時位置情報を追跡・記録・保存することはありません。</strong>
                    </p>
                </div>
                <div class="bg-slate-50 border border-slate-100 rounded-xl p-4 space-y-1.5">
                    <h3 class="text-xs font-bold text-slate-800">📊 利用履歴・スタンプ打刻情報</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        参加したスタンプラリー、獲得したスタンプ、チェックイン日時、作成・管理したスタンプラリーやベニューの設定情報を保存します。また、サービス保守・セキュリティのためのアクセスログ（IPアドレス、ブラウザ種別等）を記録する場合があります。
                    </p>
                </div>
            </div>
        </section>

        <!-- Section 2 -->
        <section class="space-y-3">
            <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                <span class="text-indigo-600 font-mono text-base">02.</span>
                <span>情報の利用目的</span>
            </h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-slate-600 leading-relaxed">
                <li>スタンプラリーの提供・運営（参加登録、チェックイン判定、スタンプ帳・完走認定証の発行など）</li>
                <li>利用者の本人確認およびアカウント管理</li>
                <li>スタンプラリー参加者一覧や進行状況の表示（ラリー設定に応じたニックネームやアバターの表示）</li>
                <li>本サービスの機能改善、不具合調査、セキュリティ維持および不正利用の防止</li>
                <li>サービス運営上重要なお知らせ・メンテナンス情報の通知</li>
            </ul>
        </section>

        <!-- Section 3 -->
        <section class="space-y-3">
            <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                <span class="text-indigo-600 font-mono text-base">03.</span>
                <span>位置情報の取り扱いに関する特記事項</span>
            </h2>
            <div class="bg-indigo-50/60 border border-indigo-100 rounded-xl p-4 text-xs sm:text-sm text-slate-700 leading-relaxed space-y-2">
                <p>
                    本サービスは位置情報（GPS）を活用したスタンプラリーサービスですが、利用者のプライバシーを尊重し、<strong>「チェックインボタンを押した瞬間のみ」</strong>端末のブラウザ経由で現在地を取得します。
                </p>
                <p>
                    取得した緯度経度は該当ベニューの判定半径内にあるかの計算にのみ利用され、サーバー側で利用者の現在地や移動経路を継続的に保存・トラッキングすることはありません。
                </p>
            </div>
        </section>

        <!-- Section 4 -->
        <section class="space-y-3">
            <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                <span class="text-indigo-600 font-mono text-base">04.</span>
                <span>個人情報の第三者提供について</span>
            </h2>
            <p class="text-sm text-slate-600 leading-relaxed">
                本サービスは、次の場合を除き、利用者の事前の同意なく個人情報を第三者に提供することはありません。
            </p>
            <ul class="list-disc list-inside space-y-1 text-xs sm:text-sm text-slate-600 leading-relaxed">
                <li>法令に基づく開示要請がある場合</li>
                <li>人の生命、身体または財産の保護のために必要がある場合であって、本人の同意を得ることが困難である場合</li>
                <li>公衆衛生の向上または児童の健全な育成の推進のために特に必要がある場合</li>
            </ul>
        </section>

        <!-- Section 5 -->
        <section class="space-y-3">
            <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                <span class="text-indigo-600 font-mono text-base">05.</span>
                <span>参加者情報の公開について</span>
            </h2>
            <p class="text-sm text-slate-600 leading-relaxed">
                利用者がスタンプラリーに参加した場合、各ラリーの設定（参加者表示モード）に応じて、ユーザー名（ハンドルネーム）、アバターアイコン、およびチェックイン状況が他の参加者やラリー管理者に公開される場合があります。公開を希望しない情報がある場合は、プロフィール設定等で調整を行ってください。
            </p>
        </section>

        <!-- Section 6 -->
        <section class="space-y-3">
            <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                <span class="text-indigo-600 font-mono text-base">06.</span>
                <span>Cookie（クッキー）の利用</span>
            </h2>
            <p class="text-sm text-slate-600 leading-relaxed">
                本サービスでは、ログイン状態の維持やセッション管理、セキュリティ確保のために Cookie を利用しています。ブラウザの設定により Cookie を無効にすることも可能ですが、その場合ログインを伴う機能が正しく動作しなくなることがありますのでご了承ください。
            </p>
        </section>

        <!-- Section 7 -->
        <section class="space-y-3">
            <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                <span class="text-indigo-600 font-mono text-base">07.</span>
                <span>プライバシーポリシーの改定</span>
            </h2>
            <p class="text-sm text-slate-600 leading-relaxed">
                本サービスは、法令の制定・改正やサービスの運用方針の変更に伴い、本プライバシーポリシーを改定することがあります。改定後のプライバシーポリシーは、本サービス上に掲載された時点より適用されます。
            </p>
        </section>

        <!-- Section 8 -->
        <section class="space-y-3">
            <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                <span class="text-indigo-600 font-mono text-base">08.</span>
                <span>お問い合わせ</span>
            </h2>
            <p class="text-sm text-slate-600 leading-relaxed">
                本プライバシーポリシーや個人情報の取り扱いに関するご質問、ご意見がございましたら、以下の GitHub リポジトリ（Issue 等）までお問い合わせください。
            </p>
            <div class="pt-1">
                <a href="https://github.com/koyhoge/stampippi" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-xs font-bold text-indigo-600 hover:text-indigo-700 bg-indigo-50 hover:bg-indigo-100 px-3.5 py-2 rounded-xl transition">
                    <span>🐙 GitHub: koyhoge/stampippi</span>
                    <span>↗</span>
                </a>
            </div>
        </section>

        <!-- Footer Date -->
        <div class="border-t border-slate-100 pt-6 text-right text-xs text-slate-400 space-y-1">
            <p>制定日: 2024年1月11日</p>
            <p>最終改定日: 2026年11月12日</p>
        </div>
    </div>
</div>
@endsection
