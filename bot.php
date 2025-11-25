<?php

// إعدادات التطبيق
define('FACEBOOK_PAGE_ACCESS_TOKEN', 'EAARRlvmJ1MMBP0wsO1KsTftohbeoQP26s3sErFqMz87Ewtw0rZCSOkafb8C7ZCWpILLpcwnFNSiZABgOj7mYdOzPrKJ5WjCm6NVQD2ijl70MalskCOFk8HcZAr1k0XEJhWqOo2R61xGm2mQYFKccPmu06ae7bhPa7omiNmiE1jUk5Q5Tsf0eOb2u9McLLDsjAyHcSntr6QZDZD');
define('FACEBOOK_GRAPH_API_URL', 'https://graph.facebook.com/v11.0/me/messages');
define('WEBHOOK_VERIFY_TOKEN', 'Nactivi_2025');

// إعداد قاعدة البيانات
function init_database() {
    $db = new SQLite3('users.db');
    $db->exec('
        CREATE TABLE IF NOT EXISTS users (
            msisdn TEXT PRIMARY KEY,
            refresh_token TEXT NOT NULL,
            access_token TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ');
    $db->close();
}

function get_user_from_db($msisdn) {
    $db = new SQLite3('users.db');
    $stmt = $db->prepare('SELECT * FROM users WHERE msisdn = ?');
    $stmt->bindValue(1, $msisdn, SQLITE3_TEXT);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);
    $db->close();
    return $user;
}

function save_user_to_db($msisdn, $refresh_token, $access_token = null) {
    $db = new SQLite3('users.db');
    $stmt = $db->prepare('
        INSERT OR REPLACE INTO users (msisdn, refresh_token, access_token, updated_at)
        VALUES (?, ?, ?, CURRENT_TIMESTAMP)
    ');
    $stmt->bindValue(1, $msisdn, SQLITE3_TEXT);
    $stmt->bindValue(2, $refresh_token, SQLITE3_TEXT);
    $stmt->bindValue(3, $access_token, SQLITE3_TEXT);
    $stmt->execute();
    $db->close();
}

function update_access_token($msisdn, $access_token) {
    $db = new SQLite3('users.db');
    $stmt = $db->prepare('
        UPDATE users SET access_token = ?, updated_at = CURRENT_TIMESTAMP
        WHERE msisdn = ?
    ');
    $stmt->bindValue(1, $access_token, SQLITE3_TEXT);
    $stmt->bindValue(2, $msisdn, SQLITE3_TEXT);
    $stmt->execute();
    $db->close();
}

// دالة واحدة للـ Quick Replies
function get_quick_replies() {
    return [
        [
            "content_type" => "text",
            "title" => "تفعيل 2G🎉🎁",
            "payload" => "GIFTWALKWIN"
        ],
        [
            "content_type" => "text",
            "title" => "عرض🔖70دج[4جيقا]",
            "payload" => "BTLINTSPEEDDAY2Go"
        ],
        [
            "content_type" => "text",
            "title" => "عرض 1Go/100Da🎁❤️",
            "payload" => "DOVINTSPEEDDAY1GoPRE"
        ],
        [
            "content_type" => "text",
            "title" => "ارسال دعوة",
            "payload" => "SEND_INVITATION"
        ]
    ];
}

// تهيئة قاعدة البيانات عند بدء التشغيل
init_database();

function send_facebook_message($recipient_id, $message_text, $quick_replies = null) {
    $url = FACEBOOK_GRAPH_API_URL;
    $params = [
        "access_token" => FACEBOOK_PAGE_ACCESS_TOKEN
    ];
    
    $data = [
        "recipient" => [
            "id" => $recipient_id
        ],
        "message" => [
            "text" => $message_text
        ]
    ];
    
    if ($quick_replies) {
        $data["message"]["quick_replies"] = $quick_replies;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($params));
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code != 200) {
        error_log("Error sending message: " . $response);
    }
}

function register_msisdn($msisdn) {
    $url = "https://apim.djezzy.dz/oauth2/registration";
    $msisdn = '213' . substr($msisdn, 1);

    $data = [
        "scope" => "smsotp",
        "client_id" => "6E6CwTkp8H1CyQxraPmcEJPQ7xka",
        "msisdn" => $msisdn
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/x-www-form-urlencoded",
        "Accept: */*",
        "User-Agent: Dalvik/2.1.0 (Linux; U; Android 8.1; SM-J230 Build/MRA58K)",
        "Connection: Keep-Alive",
        "Accept-Encoding: gzip"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200) {
        return [true, $msisdn];
    } else {
        error_log("Registration error: HTTP $http_code - $response");
        return [false, null];
    }
}

function get_auth_token($msisdn, $otp) {
    $url = "https://apim.djezzy.dz/oauth2/token";
    
    $data = [
        "scope" => "openid",
        "client_secret" => "MVpXHW_ImuMsxKIwrJpoVVMHjRsa",
        "client_id" => "6E6CwTkp8H1CyQxraPmcEJPQ7xka",
        "otp" => $otp,
        "mobileNumber" => $msisdn,
        "grant_type" => "mobile"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/x-www-form-urlencoded",
        "Accept: */*",
        "User-Agent: Dalvik/2.1.0 (Linux; U; Android 6.0; LG-X230 Build/MRA58K)",
        "Connection: Keep-Alive",
        "Accept-Encoding: gzip"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200) {
        $response_data = json_decode($response, true);
        return [true, $response_data['refresh_token']];
    } else {
        error_log("Auth token error: HTTP $http_code - $response");
        return [false, null];
    }
}

function refresh_access_token($refresh_token) {
    $url = "https://apim.djezzy.dz/oauth2/token";
    
    $data = [
        "scope" => "openid",
        "client_secret" => "MVpXHW_ImuMsxKIwrJpoVVMHjRsa",
        "client_id" => "6E6CwTkp8H1CyQxraPmcEJPQ7xka",
        "grant_type" => "refresh_token",
        "refresh_token" => $refresh_token
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/x-www-form-urlencoded",
        "Accept: */*",
        "User-Agent: Dalvik/2.1.0 (Linux; U; Android 6.0; LG-X230 Build/MRA58K)",
        "Connection: Keep-Alive",
        "Accept-Encoding: gzip"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200) {
        $response_data = json_decode($response, true);
        return [true, $response_data['access_token']];
    } else {
        error_log("Refresh token error: HTTP $http_code - $response");
        return [false, null];
    }
}

function get_access_token_for_user($msisdn) {
    $user = get_user_from_db($msisdn);
    if ($user) {
        $refresh_token = $user['refresh_token'];
        $stored_access_token = $user['access_token'];
        
        if ($stored_access_token) {
            return [true, $stored_access_token];
        }
        
        list($success, $new_access_token) = refresh_access_token($refresh_token);
        if ($success) {
            update_access_token($msisdn, $new_access_token);
            return [true, $new_access_token];
        }
    }
    
    return [false, null];
}

function send_subscription_request($access_token, $msisdn, $product_data) {
    $url = "https://apim.djezzy.dz/djezzy-api/api/v1/subscribers/{$msisdn}/subscription-product?include=";
    
    $headers = [
        "Content-Type: application/json",
        "Authorization: Bearer {$access_token}",
        "User-Agent: Dalvik/2.1.0 (Linux; U; Android 8.0; OS-G23 Build/MRA58K)",
        "x-csrf-token: wZwt1qJ/EIuSN/LvWzzqOEbTA9F+PNHp2SaAN2YI5qfjTvFy3yU/iSLormoiLVTaMuogXukq2MSFf0XyQOHcqfj5BS9GHYdEfo6tsgW4YvTR5KZjII9/DbcBkZpYKDm0g14twlFvJphrqd9ZBA8MalNKtPS6VqjVdeQMW9jyN1inNnmIESkw+pS0VqZMlqJe0Y9nUbzWElOf99b7PQl779zVh7LJTp/vrfhgTeDBb38RsTVfuB+fIivGVO2eI9LgE6fLLHJGPsnfBApr/3XeLgvbPQ9QizvG14kNxotC/M4c2hNXZU7x0vXC7BOKVrPPfyJHcC/F3PqsQz+7kbXw+HXMgQE1JFjGYoz1Lh1lBTEyiydMDz0tC8E7gZph2228DhVOXJso4Y72SFE0VSfSjGrtSxLYvQvUFWH25OdUwn5HLUFmPpm9M5e8UmL7sqJ+dqM0UlW7o1uF9qsWPuy3j54Ee9XOU+y0wkUsgkMlwUcabT3AzhmI71LfhYrOa/lfiAa1pgL3eXy21e4ExIflYtrWapwJ+Iu7Ovq33hsmGO7Ru4ldLEMekvGwn7oJFtR3i+l9oNUswRaKU0GnutYxf0sEGQsFxhrLU80H6FI7nrqcw9rmh01WjlKhSWIqEHPvtebt4bJCoaP3oZK+nYP2nOmkl6GH7iycJtSypSrGadalcsHn4BUmNukGD0sa189wvYU5hw5O94HBz9FF0ahv2W/32xCd9juXgnsAaKFzAOyWLIS4p5yyuApcnhVgq49AGdbmtkruktiBCF/F/u5J0GNGWnh1XVZdxfVVOgukb68nlud0XK+d6S3hCKnK50HyEuMTzwdu8qYfdl3iSZOK3H//DNMPw091dELVscS+ML7SeEskuXMEwZvvh9+VLWvW74QxZ5TydZ6yAeibISXQF5A==",
        "Connection: Keep-Alive",
        "Accept-Encoding: gzip"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($product_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200) {
        return true;
    } else {
        error_log("Subscription error: HTTP $http_code - $response");
        return false;
    }
}

function send_subscription_product1_request($access_token, $msisdn) {
    $data = [
        "data" => [
            "id" => "GIFTWALKWIN",
            "type" => "products",
            "meta" => [
                "services" => [
                    "steps" => 10000,
                    "code" => "GIFTWALKWIN2GO",
                    "id" => "WALKWIN"
                ]
            ]
        ]
    ];
    
    return send_subscription_request($access_token, $msisdn, $data);
}

function send_subscription_product2_request($access_token, $msisdn) {
    $data = [
        "data" => [
            "id" => "BTLINTSPEEDDAY2Go",
            "type" => "products"
        ]
    ];
    
    return send_subscription_request($access_token, $msisdn, $data);
}

function send_subscription_1go_request($access_token, $msisdn) {
    $url = "https://apim.djezzy.dz/djezzy-api/api/v1/subscribers/{$msisdn}/subscription-product?include=";
    
    $headers = [
        "User-Agent: Djezzy/2.7.5",
        "Connection: Keep-Alive",
        "Accept: */*",
        "Accept-Encoding: gzip",
        "Authorization: Bearer {$access_token}",
        "Content-Type: application/json; charset=utf-8",
        "x-csrf-token: 94u26y+DLq/dOQvv+brtfXtTiNx9ETySkLpqNy2wunLuSAzmQG6FY5GBUj3G38tktmKhdbzg9cIysOCGGjRB0eI6AM9DIh9QaOHRcx2EcFsotXDjTtuiWZdOfThAoeutA8wJAP8laKgxY2IwOcebGito22Kx63V3rLKBCqMYLq5mi6NVBkmBKB4hKhBsbV3BXSmrEw+vwZzguP5KjH10gWDsN1Fotz5p7GjZPk+iKUf+MnjSdlB5V1CWaqtcwppHClWEaSiP1nbrNblXV0w3IyBaGysNsOgEMxMdhWZitehTUcZbf+9sQl5Q64fLFkWfWNF10tvwI25rbjdnC5A6WLxsZEfc8smHYnIwTv2Y5r1w2idQDOSITqa2dULn0lzPF8rQ4l8cN8coU+u5MQivqyJ0ipuxHsNR0oaFI/0L81SvdnQMBya9nNgL5dAJIac82yN2ab1yU0/oQ4FxkNjUorKzeoxXvSAwDFbuFIyYHeLP++IabRhNFKWiUvRJ30xpjJGCMsQpkkbl2DCAmqaIjt2fwqNqFgLbjMGMd2T29HoBYGrKIfU7LDB7OnoJIHFbs7TunDSNsKv8w7D9wkiyenp8Af0zV5/Kf4xZCRkZjlWVlvBWAw3lYvDOQxNTSCzVxxB/KYY22kCEVJRxywnULCNymRWqdzTUB1oc8GO9z1t6HWddJLbSavIB80SY5CEATIi+qxQOPHgKHXym9RHy2Xx0i7cErkl0OcaeumHX1JjSXgEYqXYS6w9z+eB7FsFuZPSSmmtfnwjlHZ23+wqRAhHs4scWAr1m59FvSHiW3Fr5zchlW5oedwCjXDxfrGrT0tycYQ6O/r+y8ImEoVOBLMOqmSrMdBd3DR38gW0KeCCayXYYn5UdYU24l3SF/kZ9"
    ];
    
    $data = [
        "data" => [
            "id" => "DOVINTSPEEDDAY1GoPRE",
            "type" => "products"
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $http_code == 200;
}

function send_invitation_request($access_token, $msisdn, $b_number) {
    $url = "https://apim.djezzy.dz/djezzy-api/api/v1/subscribers/{$msisdn}/member-get-member?include=";
    $b_number = '213' . substr($b_number, 1);
    
    $headers = [
        "Accept: */*",
        "Content-Type: application/json; charset=utf-8",
        "User-Agent: Djezzy/2.6.7",
        "Connection: Keep-Alive",
        "x-csrf-token: wZwt1qJ/EIuSN/LvWzzqOEbTA9F+PNHp2SaAN2YI5qfjTvFy3yU/iSLormoiLVTaMuogXukq2MSFf0XyQOHcqfj5BS9GHYdEfo6tsgW4YvTR5KZjII9/DbcBkZpYKDm0g14twlFvJphrqd9ZBA8MalNKtPS6VqjVdeQMW9jyN1inNnmIESkw+pS0VqZMlqJe0Y9nUbzWElOf99b7PQl779zVh7LJTp/vrfhgTeDBb38RsTVfuB+fIivGVO2eI9LgE6fLLHJGPsnfBApr/3XeLgvbPQ9QizvG14kNxotC/M4c2hNXZU7x0vXC7BOKVrPPfyJHcC/F3PqsQz+7kbXw+HXMgQE1JFjGYoz1Lh1lBTEyiydMDz0tC8E7gZph2228DhVOXJso4Y72SFE0VSfSjGrtSxLYvQvUFWH25OdUwn5HLUFmPpm9M5e8UmL7sqJ+dqM0UlW7o1uF9qsWPuy3j54Ee9XOU+y0wkUsgkMlwUcabT3AzhmI71LfhYrOa/lfiAa1pgL3eXy21e4ExIflYtrWapwJ+Iu7Ovq33hsmGO7Ru4ldLEMekvGwn7oJFtR3i+l9oNUswRaKU0GnutYxf0sEGQsFxhrLU80H6FI7nrqcw9rmh01WjlKhSWIqEHPvtebt4bJCoaP3oZK+nYP2nOmkl6GH7iycJtSypSrGadalcsHn4BUmNukGD0sa189wvYU5hw5O94HBz9FF0ahv2W/32xCd9juXgnsAaKFzAOyWLIS4p5yyuApcnhVgq49AGdbmtkruktiBCF/F/u5J0GNGWnh1XVZdxfVVOgukb68nlud0XK+d6S3hCKnK50HyEuMTzwdu8qYfdl3iSZOK3H//DNMPw091dELVscS+ML7SeEskuXMEwZvvh9+VLWvW74QxZ5TydZ6yAeibISXQF5A==",
        "Authorization: Bearer {$access_token}"
    ];

    $data = [
        "data" => [
            "id" => "MGM-BONUS",
            "type" => "products",
            "meta" => [
                "services" => [
                    "b-number" => $b_number,
                    "id" => "MemberGetMember"
                ]
            ]
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200) {
        $response_data = json_decode($response, true);
        if (isset($response_data["body"])) {
            $body = json_decode($response_data["body"], true);
            if ($body["code"] == 200 && $body["message"] == "OK") {
                return [true, "OK"];
            } elseif ($body["code"] == 429 && $body["message"] == "INVITATIONS_LIMIT_REACHED") {
                return [false, "INVITATIONS_LIMIT_REACHED"];
            } elseif ($body["code"] == 419 && $body["message"] == "B_NUMBER_ACCEPTED_INVITATION") {
                return [false, "B_NUMBER_ACCEPTED_INVITATION"];
            } elseif ($body["code"] == 500 && $body["message"] == "INTERNAL_ERROR") {
                return [false, "INTERNAL_ERROR"];
            }
        }
    }
    
    return [false, "UNKNOWN_ERROR"];
}

function handle_message($sender_id, $message_text, &$user_states) {
    preg_match('/\d{10}/', $message_text, $msisdn_matches);
    $msisdn = $msisdn_matches[0] ?? null;
    
    if (!isset($user_states[$sender_id])) {
        $welcome_msg = "مرحبا 👋 بك في بوت تسجيل جيزي الجديد! 🎉

✔️ الميزات الجديدة:
• حفظ الأرقام المسجلة مسبقاً
• لا حاجة لإعادة إدخال الرمز بعد التسجيل الأول
• تفعيل سريع للعروض

أدخل رقم جيزي الخاص بك (يبدأ بـ 07):";
        send_facebook_message($sender_id, $welcome_msg);
        $user_states[$sender_id] = ["stage" => "awaiting_msisdn"];
        return;
    }

    $state = $user_states[$sender_id];

    if (strpos($message_text, "👍") !== false) {
        send_facebook_message($sender_id, "🤖");
        return;
    }

    if ($msisdn) {
        if (strpos($msisdn, "05") === 0) {
            send_facebook_message($sender_id, "سيتم اضافة اوريدو قريبا 💻");
            return;
        } elseif (strpos($msisdn, "06") === 0) {
            send_facebook_message($sender_id, "لايوجد تسجيل موبيليس ❌");
            return;
        }
    }

    if ($state["stage"] == "awaiting_msisdn") {
        $msisdn = preg_replace('/\D/', '', $message_text);
        if (strlen($msisdn) == 10 && strpos($msisdn, "07") === 0) {
            $formatted_msisdn = '213' . substr($msisdn, 1);
            $user_data = get_user_from_db($formatted_msisdn);
            
            if ($user_data) {
                send_facebook_message($sender_id, "🔓 مرحباً بعودتك! الرقم " . substr($msisdn, 0, 4) . "xxxx" . substr($msisdn, -2) . " مسجل مسبقاً.");
                list($success, $access_token) = get_access_token_for_user($formatted_msisdn);
                if ($success) {
                    send_facebook_message($sender_id, "اختر العرض 🔖:", get_quick_replies());
                    $user_states[$sender_id] = [
                        "stage" => "awaiting_confirmation", 
                        "msisdn" => $formatted_msisdn, 
                        "access_token" => $access_token
                    ];
                } else {
                    send_facebook_message($sender_id, "⚠️ حدث خطأ في تفعيل الرقم المسجل. يرجى إعادة التسجيل.");
                    $user_states[$sender_id] = ["stage" => "awaiting_msisdn"];
                }
            } else {
                list($success, $registered_msisdn) = register_msisdn($msisdn);
                if ($success) {
                    send_facebook_message($sender_id, "📱 تم إرسال رسالة نصية إلى " . substr($msisdn, 0, 4) . "xxxx" . substr($msisdn, -2) . " تحتوي على الرمز.");
                    $user_states[$sender_id] = ["stage" => "awaiting_otp", "msisdn" => $registered_msisdn];
                } else {
                    send_facebook_message($sender_id, "❌ خطا في السيرفر");
                }
            }
        } else {
            send_facebook_message($sender_id, "❌ يرجى ادخال ارقام جيزي فقط (يبدأ بـ 07)");
        }
    } elseif ($state["stage"] == "awaiting_otp") {
        preg_match('/\d{6}/', $message_text, $otp_matches);
        preg_match('/\d{10}/', $message_text, $msisdn_matches);
        $otp = $otp_matches[0] ?? null;
        $new_msisdn = $msisdn_matches[0] ?? null;

        if ($new_msisdn && strpos($new_msisdn, "07") === 0) {
            list($success, $registered_msisdn) = register_msisdn($new_msisdn);
            if ($success) {
                send_facebook_message($sender_id, "📱 تم إرسال رسالة نصية إلى " . substr($new_msisdn, 0, 4) . "xxxx" . substr($new_msisdn, -2) . " تحتوي على الرمز.");
                $user_states[$sender_id] = ["stage" => "awaiting_otp", "msisdn" => $registered_msisdn];
            } else {
                send_facebook_message($sender_id, "❌ الرمز المدرج خاطئ، اعد ارسال رقمك للحصول على رمز جديد 📩");
            }
        } elseif ($otp) {
            list($success, $refresh_token) = get_auth_token($state["msisdn"], $otp);
            if ($success) {
                list($success, $access_token) = refresh_access_token($refresh_token);
                if ($success) {
                    save_user_to_db($state["msisdn"], $refresh_token, $access_token);
                    
                    send_facebook_message($sender_id, "🎉 تم تسجيل رقمك بنجاح! الآن يمكنك استخدامه بدون رمز في المرة القادمة.\n\nاختر العرض 🔖:", get_quick_replies());
                    $user_states[$sender_id] = [
                        "stage" => "awaiting_confirmation", 
                        "msisdn" => $state["msisdn"], 
                        "access_token" => $access_token
                    ];
                } else {
                    send_facebook_message($sender_id, "❌ خطا في سيرفر Djezzy اعد المحاولة بعد 5 دقائق");
                    $user_states[$sender_id] = ["stage" => "awaiting_msisdn"];
                }
            } else {
                send_facebook_message($sender_id, "❌ الرمز المدرج خاطئ، اعد ارسال رقمك للحصول على رمز جديد 📩");
            }
        } else {
            send_facebook_message($sender_id, "❌ الرمز المدرج خاطئ، اعد ارسال رقمك للحصول على رمز جديد 📩");
        }
    } elseif ($state["stage"] == "awaiting_confirmation") {
        if ($message_text == "تفعيل 2G🎉🎁") {
            $subscription_success = send_subscription_product1_request($state["access_token"], $state["msisdn"]);
            if ($subscription_success) {
                send_facebook_message($sender_id, "🎉 " . substr($state['msisdn'], 3, 4) . "xxxx" . substr($state['msisdn'], -2) . " تم تفعيل 2G بنجاح!");
            } else {
                send_facebook_message($sender_id, "⚠️ حدث خطأ - يبدو انك سجلت مسبقاً ولم تكمل اسبوعا 📆");
            }
            $user_states[$sender_id] = ["stage" => "awaiting_msisdn"];
        } elseif ($message_text == "عرض🔖70دج[4جيقا]") {
            $subscription_success = send_subscription_product2_request($state["access_token"], $state["msisdn"]);
            if ($subscription_success) {
                send_facebook_message($sender_id, "🎉 " . substr($state['msisdn'], 3, 4) . "xxxx" . substr($state['msisdn'], -2) . " تم تفعيل العرض بنجاح! 😁");
            } else {
                send_facebook_message($sender_id, "⚠️ حدث خطا - رصيدك غير كافي💰 لتفعيل هذا العرض🔖");
            }
            $user_states[$sender_id] = ["stage" => "awaiting_msisdn"];
        } elseif ($message_text == "عرض 1Go/100Da🎁❤️") {
            $subscription_success = send_subscription_1go_request($state["access_token"], $state["msisdn"]);
            if ($subscription_success) {
                send_facebook_message($sender_id, "🎉 " . substr($state['msisdn'], 3, 4) . "xxxx" . substr($state['msisdn'], -2) . " تم تفعيل عرض 1Go/100Da بنجاح! 😁");
            } else {
                send_facebook_message($sender_id, "⚠️ حدث خطا - رصيدك غير كافي💰 لتفعيل هذا العرض🔖");
            }
            $user_states[$sender_id] = ["stage" => "awaiting_msisdn"];
        } elseif ($message_text == "ارسال دعوة") {
            send_facebook_message($sender_id, "الرجاء إدخال رقم الهاتف الذي تريد إرسال الدعوة إليه (يجب أن يبدأ بـ 07):");
            $user_states[$sender_id] = [
                "stage" => "awaiting_invitation_number", 
                "msisdn" => $state["msisdn"], 
                "access_token" => $state["access_token"]
            ];
        }
    } elseif ($state["stage"] == "awaiting_invitation_number") {
        $b_number = preg_replace('/\D/', '', $message_text);
        if (strlen($b_number) == 10 && strpos($b_number, "07") === 0) {
            $success_count = 0;
            $error_messages = [];
            
            for ($i = 0; $i < 3; $i++) {
                list($invitation_success, $response_message) = send_invitation_request($state["access_token"], $state["msisdn"], $b_number);
                if ($invitation_success) {
                    $success_count++;
                } else {
                    $error_messages[] = $response_message;
                }
            }
            
            if ($success_count > 0) {
                send_facebook_message($sender_id, "تم ارسال الدعوة بنجاح إلى " . substr($b_number, 0, 4) . "xxxx" . substr($b_number, -2) . "   الان ماعليك فعله هو فقط ارسال الرقم المدعو الينا والرمز الذي  وصل في رسالة DJEZZY APP او يمكنك الدخول فقط لتطبيق جيزي بالرقم المدعو");
            } else {
                if (in_array("INVITATIONS_LIMIT_REACHED", $error_messages)) {
                    send_facebook_message($sender_id, "لم تكمل اسبوعا");
                } elseif (in_array("B_NUMBER_ACCEPTED_INVITATION", $error_messages)) {
                    send_facebook_message($sender_id, "لقد تمت دعوة هذا الرقم من قبل");
                } elseif (in_array("INTERNAL_ERROR", $error_messages)) {
                    send_facebook_message($sender_id, "خطا في البيانات");
                } else {
                    send_facebook_message($sender_id, "خطا في السيرفر اعد المحاولة لاحقا                     من الاحسن المحاولة في الاوقات من الساعة 02 ليلا الى 10 صباحا ");
                }
            }
        } else {
            send_facebook_message($sender_id, "رقم الهاتف غير صحيح، يرجى إدخال رقم جيزي يبدأ بـ 07");
        }
        
        $user_states[$sender_id] = ["stage" => "awaiting_msisdn"];
    }
}

// =============================================
// معالجة ويب هوك - الجزء الرئيسي
// =============================================

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // التحقق من الويب هوك
    $token = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? '';
    
    if ($token === WEBHOOK_VERIFY_TOKEN) {
        echo $challenge;
        exit;
    } else {
        http_response_code(403);
        echo "Verification failed";
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // استقبال الأحداث من فيسبوك
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    error_log("Webhook received: " . print_r($data, true));
    
    if (isset($data['object']) && $data['object'] === 'page') {
        foreach ($data['entry'] as $entry) {
            $page_id = $entry['id'];
            $time = $entry['time'];
            
            foreach ($entry['messaging'] as $messaging_event) {
                if (isset($messaging_event['message'])) {
                    $sender_id = $messaging_event['sender']['id'];
                    $message_text = $messaging_event['message']['text'] ?? '';
                    $message_id = $messaging_event['message']['mid'] ?? '';
                    
                    // معالجة الرسالة
                    handle_webhook_message($sender_id, $message_text, $message_id);
                } elseif (isset($messaging_event['postback'])) {
                    // معالجة Postback (Quick Replies)
                    $sender_id = $messaging_event['sender']['id'];
                    $payload = $messaging_event['postback']['payload'] ?? '';
                    
                    if ($payload) {
                        handle_webhook_message($sender_id, $payload, uniqid());
                    }
                }
            }
        }
        echo "EVENT_RECEIVED";
    } else {
        http_response_code(404);
        echo "Invalid request";
    }
    exit;
}

// دالة معالجة الرسائل من الويب هوك
function handle_webhook_message($sender_id, $message_text, $message_id) {
    static $user_states = [];
    static $processed_message_ids = [];
    
    // تجنب معالجة الرسائل المكررة
    if (in_array($message_id, $processed_message_ids)) {
        return;
    }
    
    handle_message($sender_id, $message_text, $user_states);
    $processed_message_ids[] = $message_id;
    
    // الحفاظ على حجم المصفوفة معقول
    if (count($processed_message_ids) > 1000) {
        array_shift($processed_message_ids);
    }
}

// إظهار رسالة إذا تم الوصول للصفحة مباشرة
echo "WhatsApp Bot is running! Webhook URL is ready for Facebook.";

?>