<?php
error_reporting(0);

/* ================= SOZLAMALAR ================= */
$token = "8381839257:AAH-FSIdrA6oFbpA_er1vLAKwlN4-xVy1e8";
$main_admin = 1438837962; // sizning ID
$kanalcha = "@topkinoneww";
$bot = "@Topkinonewwbot";

/* ================= PAPKALAR ================= */
@mkdir("data");
@mkdir("data/users");
@mkdir("data/step");
@mkdir("data/kino");

if(!file_exists("data/kino/son.txt")) file_put_contents("data/kino/son.txt","0");
if(!file_exists("data/admins.txt")) file_put_contents("data/admins.txt",$main_admin);

/* ================= FUNKSIYALAR ================= */
function bot($method,$data=[]){
    global $token;
    $url = "https://api.telegram.org/bot$token/$method";
    $ch = curl_init();
    curl_setopt_array($ch,[
        CURLOPT_URL=>$url,
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_POSTFIELDS=>$data
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res);
}

function joinchat($cid){
    global $kanalcha;
    $res = bot("getChatMember",[
        "chat_id"=>$kanalcha,
        "user_id"=>$cid
    ]);
    if($res->result->status=="left") return false;
    return true;
}

function isAdmin($cid){
    $admins = file("data/admins.txt", FILE_IGNORE_NEW_LINES);
    return in_array($cid,$admins);
}

/* ================= UPDATE ================= */
$update = json_decode(file_get_contents("php://input"));
$message = $update->message;
$callback = $update->callback_query;

if($message){
    $cid = $message->chat->id;
    $text = trim($message->text);
    $uid = $message->from->id;
}

if($callback){
    $cid = $callback->message->chat->id;
    $data = $callback->data;
    $mid = $callback->message->message_id;
}

/* ================= FOYDALANUVCHI ================= */
if($cid && !file_exists("data/users/$cid.txt")){
    file_put_contents("data/users/$cid.txt","1");
}

/* ================= START ================= */
if($text=="/start"){
    if(!joinchat($cid)){
        bot("sendMessage",[
            "chat_id"=>$cid,
            "text"=>"📛 Botdan foydalanish uchun kanalga obuna bo‘ling!",
            "reply_markup"=>json_encode([
                "inline_keyboard"=>[
                    [["text"=>"📢 Kanal","url"=>"https://t.me/".str_replace("@","",$kanalcha)]],
                    [["text"=>"✅ Tekshirish","callback_data"=>"check"]]
                ]
            ])
        ]);
        exit;
    }

    bot("sendMessage",[
        "chat_id"=>$cid,
        "text"=>"🎬 *$bot*\n\n🎥 Kino kodini yuboring:",
        "parse_mode"=>"Markdown"
    ]);
}

/* ================= CALLBACK ================= */
if($data=="check"){
    if(joinchat($cid)){
        bot("editMessageText",[
            "chat_id"=>$cid,
            "message_id"=>$mid,
            "text"=>"✅ Obuna tasdiqlandi!\n\n🎬 Kino kodini yuboring:"
        ]);
    } else {
        bot("answerCallbackQuery",[
            "callback_query_id"=>$callback->id,
            "text"=>"❌ Hali obuna emassiz!",
            "show_alert"=>true
        ]);
    }
}

/* ================= ADMIN PANEL ================= */
if(($text=="/admin" || $text=="🔙 Admin panel") && isAdmin($cid)){
    unlink("data/step/$cid.txt");
    bot("sendMessage",[
        "chat_id"=>$cid,
        "text"=>"👮‍♂️ *ADMIN PANEL*",
        "parse_mode"=>"Markdown",
        "reply_markup"=>json_encode([
            "keyboard"=>[
                ["➕ Kino joylash","🗑 Kino o‘chirish"],
                ["👤 Admin qo‘shish","📊 Statistika"]
            ],
            "resize_keyboard"=>true
        ])
    ]);
}

/* ================= STATISTIKA ================= */
if($text=="📊 Statistika" && isAdmin($cid)){
    $users = count(scandir("data/users"))-2;
    $kino = file_get_contents("data/kino/son.txt");
    $admins = count(file("data/admins.txt"));

    bot("sendMessage",[
        "chat_id"=>$cid,
        "text"=>"📊 *Statistika*\n\n👥 Users: $users\n🎬 Kinolar: $kino\n👮 Adminlar: $admins",
        "parse_mode"=>"Markdown"
    ]);
}

/* ================= KINO QO‘SHISH ================= */
if($text=="➕ Kino joylash" && isAdmin($cid)){
    file_put_contents("data/step/$cid.txt","add_kino");
    bot("sendMessage",[
        "chat_id"=>$cid,
        "text"=>"🎥 Video yuboring:"
    ]);
}

/* ================= KINO O‘CHIRISH ================= */
if($text=="🗑 Kino o‘chirish" && isAdmin($cid)){
    file_put_contents("data/step/$cid.txt","del_kino");
    bot("sendMessage",[
        "chat_id"=>$cid,
        "text"=>"🗑 O‘chiriladigan kino kodini yuboring:"
    ]);
}

/* ================= ADMIN QO‘SHISH ================= */
if($text=="👤 Admin qo‘shish" && isAdmin($cid)){
    file_put_contents("data/step/$cid.txt","add_admin");
    bot("sendMessage",[
        "chat_id"=>$cid,
        "text"=>"👤 Yangi admin ID yuboring:"
    ]);
}

$step = file_exists("data/step/$cid.txt") ? file_get_contents("data/step/$cid.txt") : "";

/* ================= STEP: KINO QO‘SHISH ================= */
if($step=="add_kino" && isset($message->video)){
    $id = file_get_contents("data/kino/son.txt")+1;
    file_put_contents("data/kino/son.txt",$id);
    file_put_contents("data/kino/$id.txt",$message->video->file_id);
    unlink("data/step/$cid.txt");

    bot("sendMessage",[
        "chat_id"=>$cid,
        "text"=>"✅ Kino joylandi!\n🎬 Kod: *$id*",
        "parse_mode"=>"Markdown"
    ]);
}

/* ================= STEP: KINO O‘CHIRISH ================= */
if($step=="del_kino" && is_numeric($text)){
    if(file_exists("data/kino/$text.txt")){
        unlink("data/kino/$text.txt");
        unlink("data/step/$cid.txt");
        bot("sendMessage",[
            "chat_id"=>$cid,
            "text"=>"✅ Kino o‘chirildi (Kod: $text)"
        ]);
    } else {
        bot("sendMessage",[
            "chat_id"=>$cid,
            "text"=>"❌ Bunday kodli kino yo‘q"
        ]);
    }
}

/* ================= STEP: ADMIN QO‘SHISH ================= */
if($step=="add_admin" && is_numeric($text)){
    $admins = file("data/admins.txt", FILE_IGNORE_NEW_LINES);
    if(!in_array($text,$admins)){
        file_put_contents("data/admins.txt",$text."\n",FILE_APPEND);
        unlink("data/step/$cid.txt");
        bot("sendMessage",[
            "chat_id"=>$cid,
            "text"=>"✅ Admin qo‘shildi\nID: $text"
        ]);
    } else {
        bot("sendMessage",[
            "chat_id"=>$cid,
            "text"=>"⚠️ Bu foydalanuvchi allaqachon admin"
        ]);
    }
}

/* ================= KINO OLISH ================= */
if(is_numeric($text) && file_exists("data/kino/$text.txt")){
    $file = file_get_contents("data/kino/$text.txt");
    bot("sendVideo",[
        "chat_id"=>$cid,
        "video"=>$file,
        "caption"=>"🎬 $bot"
    ]);
}
