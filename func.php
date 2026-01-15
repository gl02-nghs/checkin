<?php
/**
 * 核心函式庫 - 簽到系統專用
 * 修正：統一視覺樣式，確保錯誤/成功頁面與主系統一致
 */

// 取得目前的版本號或時間戳，用於快取控制
function get_asset_version() {
    return time(); 
}

function display_error($msg, $type = "error") {
    $color = ($type == "error") ? "text-red-600" : "text-blue-600";
    $icon = ($type == "error") ? "⚠️" : "ℹ️";
    
    // 輸出與主系統一致的 HTML 結構
    echo "<!DOCTYPE html>
    <html lang='zh-TW'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0'>
        <title>系統訊息</title>
        <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css'>
        <style>
            body { background-color: #f8fafc; }
            .msg-card { animation: popIn 0.3s ease-out; }
            @keyframes popIn {
                0% { transform: scale(0.9); opacity: 0; }
                100% { transform: scale(1); opacity: 1; }
            }
        </style>
    </head>
    <body class='flex items-center justify-center min-h-screen p-6'>
        <div class='msg-card bg-white p-8 rounded-3xl shadow-2xl w-full max-w-sm text-center border border-slate-100'>
            <div class='text-5xl mb-4'>$icon</div>
            <div class='text-xl font-bold $color mb-4'>$msg</div>
            <div class='mt-8 pt-6 border-t border-slate-50'>
                <p class='text-xs text-slate-400'>港中線上簽到系統 2026</p>
            </div>
        </div>
    </body>
    </html>";
    exit;
}

function display_success($msg) {
    // 成功頁面增加自動跳轉或美化
    echo "<!DOCTYPE html>
    <html lang='zh-TW'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0'>
        <title>簽到成功</title>
        <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css'>
        <style>
            body { background-color: #f8fafc; }
            .success-bounce { animation: bounceIn 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55); }
            @keyframes bounceIn {
                0% { transform: scale(0.3); opacity: 0; }
                50% { transform: scale(1.05); }
                70% { transform: scale(0.9); }
                100% { transform: scale(1); opacity: 1; }
            }
        </style>
    </head>
    <body class='flex items-center justify-center min-h-screen p-6'>
        <div class='success-bounce bg-white p-10 rounded-3xl shadow-2xl w-full max-w-sm text-center border border-slate-100'>
            <div class='text-6xl mb-6'>✅</div>
            <div class='text-2xl font-black text-slate-800 mb-2'>簽到完成</div>
            <div class='text-slate-500 font-medium'>$msg</div>
            <div class='mt-8 pt-6 border-t border-slate-50'>
                <p class='text-xs text-slate-400'>港中線上簽到系統 2026</p>
            </div>
        </div>
    </body>
    </html>";
    exit;
}

function display_line_warning() {
    echo "<!DOCTYPE html>
    <html lang='zh-TW'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css'>
    </head>
    <body class='bg-slate-50 flex items-center justify-center min-h-screen p-6'>
        <div class='bg-white p-8 rounded-3xl shadow-xl w-full max-w-sm text-center'>
            <div class='text-5xl mb-4'>🚫</div>
            <h2 class='text-xl font-bold text-red-600 mb-4'>不支援 LINE 內建瀏覽器</h2>
            <p class='text-sm text-slate-600 mb-6 leading-relaxed'>
                為了確保簽名功能正常運作，<br>請點擊右上角標籤，選擇「使用預設瀏覽器開啟」。
            </p>
            <div class='bg-blue-50 p-4 rounded-2xl'>
                <p class='text-xs text-blue-700 font-bold'>操作提示：點選右上角 [三個點] → [使用預設瀏覽器開啟]</p>
            </div>
        </div>
    </body>
    </html>";
    exit;
}