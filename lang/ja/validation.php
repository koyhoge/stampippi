<?php

return [
    'accepted' => ':attributeを承認してください。',
    'active_url' => ':attributeには有効なURLを指定してください。',
    'after' => ':attributeには、:dateより後の日時を指定してください。',
    'after_or_equal' => ':attributeには、:date以降の日時を指定してください。',
    'alpha' => ':attributeには英字のみ使用できます。',
    'alpha_dash' => ':attributeには半角英数字・ハイフン(-)・アンダースコア(_)のみ使用できます。',
    'alpha_num' => ':attributeには半角英数字のみ使用できます。',
    'array' => ':attributeには配列を指定してください。',
    'before' => ':attributeには、:dateより前の日時を指定してください。',
    'before_or_equal' => ':attributeには、:date以前の日時を指定してください。',
    'between' => [
        'numeric' => ':attributeは :min 〜 :max の範囲で指定してください。',
        'file' => ':attributeは :min 〜 :max KBのファイルにしてください。',
        'string' => ':attributeは :min 〜 :max 文字で入力してください。',
        'array' => ':attributeは :min 〜 :max 個指定してください。',
    ],
    'boolean' => ':attributeには true または false を指定してください。',
    'confirmed' => ':attributeと確認用入力が一致していません。',
    'date' => ':attributeには有効な日時を入力してください。',
    'date_equals' => ':attributeには :date と同じ日時を指定してください。',
    'date_format' => ':attributeの形式が正しくありません（:format）。',
    'different' => ':attributeと:otherには異なる値を指定してください。',
    'digits' => ':attributeは :digits 桁で入力してください。',
    'digits_between' => ':attributeは :min 〜 :max 桁で入力してください。',
    'email' => '有効なメールアドレスの形式で入力してください。',
    'exists' => '選択された:attributeが見つかりません。もう一度選択してください。',
    'file' => ':attributeにはファイルを指定してください。',
    'filled' => ':attributeを入力してください。',
    'gt' => [
        'numeric' => ':attributeには :value より大きい値を指定してください。',
        'string' => ':attributeは :value 文字より長く入力してください。',
    ],
    'gte' => [
        'numeric' => ':attributeには :value 以上の値を指定してください。',
        'string' => ':attributeは :value 文字以上で入力してください。',
    ],
    'in' => '選択された:attributeが正しくありません（権限がないか無効な選択肢です）。',
    'integer' => ':attributeには整数を入力してください。',
    'max' => [
        'numeric' => ':attributeには :max 以下の数値を指定してください。',
        'file' => ':attributeには :max KB以下のファイルを指定してください。',
        'string' => ':attributeは :max 文字以内で入力してください。',
        'array' => ':attributeは :max 個以下にしてください。',
    ],
    'min' => [
        'numeric' => ':attributeには :min 以上の数値を指定してください。',
        'file' => ':attributeには :min KB以上のファイルを指定してください。',
        'string' => ':attributeは :min 文字以上で入力してください。',
        'array' => ':attributeは :min 個以上指定してください。',
    ],
    'not_in' => '選択された:attributeは使用できません。',
    'numeric' => ':attributeには数値を入力してください。',
    'present' => ':attributeが存在していません。',
    'regex' => ':attributeの形式が正しくありません。',
    'required' => ':attributeを入力してください。',
    'required_if' => ':otherが:valueの場合、:attributeを入力してください。',
    'same' => ':attributeと:otherが一致していません。',
    'size' => [
        'numeric' => ':attributeには :size を指定してください。',
        'string' => ':attributeは :size 文字で入力してください。',
    ],
    'string' => ':attributeには文字を入力してください。',
    'unique' => 'この:attributeは既に使用されています。別の値を指定してください。',
    'url' => ':attributeには正しいURL形式を入力してください。',

    /*
    |--------------------------------------------------------------------------
    | 項目別カスタムエラーメッセージ
    |--------------------------------------------------------------------------
    */
    'custom' => [
        'handle' => [
            'regex' => 'ユーザーID (@handle) は半角英数字・ハイフン(-)・アンダースコア(_)のみで入力してください。',
            'unique' => 'そのユーザーID (@handle) は既に他のユーザーに使用されています。別のIDを入力してください。',
        ],
        'slug' => [
            'regex' => 'URLスラッグ（ID）は半角英数字・ハイフン(-)・アンダースコア(_)のみで入力してください。',
            'unique' => 'そのURLスラッグ（ID）は既に使用されています。別の英数字を指定してください。',
        ],
        'email' => [
            'unique' => 'そのメールアドレスは既に登録されています。ログイン画面からログインしてください。',
        ],
        'password' => [
            'min' => 'パスワードは6文字以上で入力してください。',
            'confirmed' => 'パスワードと「パスワード確認」の入力内容が一致していません。',
        ],
        'group_id' => [
            'in' => '選択されたグループに対する操作権限がありません。',
        ],
        'rally_series_id' => [
            'required' => '所属するラリーシリーズを選択してください（グループメンバーはシリーズに属するスタンプラリーのみ作成できます）。',
            'exists' => '選択されたラリーシリーズが見つかりません。',
        ],
        'ends_at' => [
            'after_or_equal' => '開催終了日時には、開催開始日時と同じかそれより後の日時を指定してください。',
        ],
        'available_until' => [
            'after_or_equal' => '打刻可能終了日時には、打刻可能開始日時と同じかそれより後の日時を指定してください。',
        ],
        'latitude' => [
            'required' => '位置情報（緯度）が取得できませんでした。地図をクリックするかスマートフォンのGPSを許可してください。',
            'between' => '緯度は -90 〜 90 の範囲で指定してください。',
        ],
        'longitude' => [
            'required' => '位置情報（経度）が取得できませんでした。地図をクリックするかスマートフォンのGPSを許可してください。',
            'between' => '経度は -180 〜 180 の範囲で指定してください。',
        ],
        'radius_meters' => [
            'min' => '判定半径は 10m 以上で指定してください。',
            'max' => '判定半径は 10,000m（10km）以下で指定してください。',
        ],
        'stamp_color' => [
            'regex' => 'スタンプカラーはカラーコード形式（例: #4F46E5）で選択してください。',
        ],
        'stamp_icon_image' => [
            'image' => 'スタンプ絵柄画像には画像ファイルを指定してください。',
            'mimes' => 'スタンプ絵柄画像は PNG / JPG / WebP / GIF / SVG 形式のファイルを指定してください。',
            'max' => 'スタンプ絵柄画像のファイルサイズは 2MB 以下にしてください。',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 属性名（日本語表示名）
    |--------------------------------------------------------------------------
    */
    'attributes' => [
        'handle' => 'ユーザーID (@handle)',
        'name' => '表示名・名称',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'password_confirmation' => 'パスワード（確認）',
        'login' => '@handle またはメールアドレス',
        'identifier' => '指定ユーザー（@handle またはメールアドレス）',
        'role' => '権限ロール',
        'slug' => 'URLスラッグ（ID）',
        'title' => 'タイトル',
        'description' => '説明文',
        'group_id' => '所有グループ',
        'rally_series_id' => '所属ラリーシリーズ',
        'visibility' => '公開ステータス',
        'completion_condition' => 'コンプリート条件',
        'required_locations_count' => '必要獲得スタンプ数',
        'starts_at' => '開催開始日時',
        'ends_at' => '開催終了日時',
        'latitude' => '緯度',
        'longitude' => '経度',
        'accuracy' => 'GPS精度',
        'radius_meters' => '判定半径（メートル）',
        'available_from' => '打刻可能開始日時',
        'available_until' => '打刻可能終了日時',
        'stamp_icon_value' => 'スタンプアイコン',
        'stamp_icon_image' => 'スタンプ絵柄画像',
        'stamp_color' => 'スタンプカラー',
        'sort_order' => '表示順',
        'source_location_id' => 'コピー元のベニュー',
        'user_id' => '対象ユーザー',
    ],
];

