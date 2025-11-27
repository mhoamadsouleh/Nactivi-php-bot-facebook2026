# Facebook Djezzy Bot

بوت فيسبوك ماسنجر لإدارة خدمات جيزي الجزائرية.

## المميزات

- ✅ معلومات بطاقة SIM والرصيد
- ✅ تفعيل العروض التلقائية
- ✅ نظام تسجيل مبسط
- ✅ دعوة الأصدقاء
- ✅ قاعدة بيانات محلية

## النشر على Render

1. انسخ هذه الملفات إلى مستودع GitHub
2. سجل في [render.com](https://render.com)
3. أنشئ خدمة ويب جديدة من مستودع GitHub
4. استخدم الإعدادات التالية:
   - **Environment**: PHP
   - **Build Command**: (اتركه فارغاً)
   - **Start Command**: `php -S 0.0.0.0:10000 index.php`

## إعداد Facebook Messenger

1. أنشئ تطبيق في [Meta for Developers](https://developers.facebook.com/)
2. أضف منتج Messenger
3. في إعدادات Webhook:
   - **Callback URL**: `https://your-app.onrender.com`
   - **Verify Token**: `Nactivi_2025`
4. اشترك في الأحداث: `messages`, `messaging_postbacks`

## الرخصة

هذا المشروع للأغراض التعليمية فقط.