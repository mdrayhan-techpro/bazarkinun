# 🚀 BazarKinun.com — Complete Free Deployment Guide (TiDB Cloud + Render.com)

এই গাইডের মাধ্যমে আপনি **এক পয়সাও খরচ না করে (১০০% ফ্রিতে)** আপনার সম্পূর্ণ সাইট ও ডাটাবেস লাইভ সার্ভারে ডেপ্লয় করতে পারবেন।

---

## 🛠️ আর্কিটেকচার ওভারভিউ
- **ডাটাবেস:** [TiDB Cloud](https://tidbcloud.com/) Serverless (MySQL 8.0 Compatible, 5GB আজীবন ফ্রি, নো ক্রেডিট কার্ড)।
- **ওয়েব সার্ভার:** [Render.com](https://render.com/) Free Web Service (Docker Container, ফ্রি SSL/HTTPS, অটো-ডেপ্লয়)।
- **এসএমএস ওটিপি:** httpSMS (আপনার মোবাইল সিম `01332563212` দিয়ে ফ্রি এসএমএস)।
- **সার্ভার অ্যাক্টিভ রাখা:** [UptimeRobot](https://uptimerobot.com/) (প্রতি ৫ মিনিটে পিং দিয়ে রেন্ডার সার্ভার সবসময় সজাগ রাখবে)।

---

## ধাপ ১: TiDB Cloud-এ ফ্রি ডাটাবেস সেটআপ (২ মিনিট)

1. [tidbcloud.com](https://tidbcloud.com/)-এ গিয়ে একটি ফ্রি অ্যাকাউন্ট খুলুন (Google দিয়ে সাইন-ইন করা যায়)।
2. **Create Cluster**-এ ক্লিক করে **Serverless (Free)** সিলেক্ট করুন:
   - **Region:** `AWS / Singapore (ap-southeast-1)` নির্বাচন করুন (বাংলাদেশ থেকে সবচেয়ে দ্রুত গতির জন্য)।
   - **Cluster Name:** `bazarkinun-db` দিন।
   - **Create**-এ ক্লিক করুন।
3. ক্লাস্টার তৈরি হলে **SQL Editor** বা **Chat2Query** ট্যাবে যান।
4. আমাদের প্রোজেক্টের [`database/tidb_production_ready.sql`](file:///d:/Project/PHP/bazarkinundotcom/database/tidb_production_ready.sql) ফাইলের সমস্ত কোড কপি করে SQL Editor-এ পেস্ট করে **Run** চাপুন।  
   *(আপনার সমস্ত টেবিল, প্রোডাক্ট, অ্যাডমিন সেটিংস এবং RBAC পারমিশন ১ ক্লিকে TiDB-তে তৈরি হয়ে যাবে!)*
5. এবার TiDB ড্যাশবোর্ডের উপরে ডানপাশে **Connect** বাটনে ক্লিক করুন:
   - **Connect with:** `General` নির্বাচন করুন।
   - সেখান থেকে আপনার **Host**, **Port (4000)**, **User**, এবং **Password** কপি করে নোটপ্যাডে রেখে দিন।

---

## ধাপ ২: প্রোজেক্ট GitHub-এ তোলা (২ মিনিট)

আপনার লোকাল পিসিতে টার্মিনালে এই কমান্ডগুলো দিয়ে গিটহাবে একটি নতুন প্রাইভেট রিপোজিটরিতে কোড পুশ করুন:

```bash
git init
git add .
git commit -m "Production release for Render and TiDB"
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/bazarkinun.git
git push -u origin main
```

---

## ধাপ ৩: Render.com-এ ফ্রি সার্ভার ডেপ্লয় (৩ মিনিট)

1. [render.com](https://render.com/)-এ গিয়ে ফ্রি অ্যাকাউন্ট খুলুন (GitHub দিয়ে লগইন করতে পারেন)।
2. ড্যাশবোর্ডে **New +** বাটনে ক্লিক করে **Web Service** সিলেক্ট করুন।
3. আপনার **GitHub Repository** (bazarkinun) সিলেক্ট করুন।
4. নিচের সেটিংসগুলো দিন:
   - **Name:** `bazarkinun-web`
   - **Region:** `Singapore` (বাংলাদেশ থেকে মাত্র ~৪০ms লেটেন্সি!)
   - **Runtime:** `Docker` (Render স্বয়ংক্রিয়ভাবে আমাদের তৈরি `Dockerfile` ডিটেক্ট করবে)।
   - **Instance Type:** `Free` ($0/month)
5. **Environment Variables** সেকশনে নিচের ভেরিয়েবলগুলো যোগ করুন:

| Key | Value (TiDB থেকে নেওয়া) |
| :--- | :--- |
| `APP_ENV` | `production` |
| `APP_URL` | `https://bazarkinun-web.onrender.com` (বা আপনার রেন্ডার ইউআরএল) |
| `DB_HOST` | `gateway01.ap-southeast-1.prod.aws.tidbcloud.com` (আপনার TiDB Host) |
| `DB_PORT` | `4000` |
| `DB_NAME` | `bazarkinun_db` |
| `DB_USER` | `xxxxxx.root` (আপনার TiDB Username) |
| `DB_PASS` | আপনার TiDB ক্লাস্টারের Password |
| `DB_SSL` | `true` |
| `CURL_VERIFY_SSL` | `true` |

6. **Create Web Service**-এ ক্লিক করুন!  
   *Render মাত্র ৩-৪ মিনিটে ডকার ইমেজ বিল্ড করে আপনাকে একটি লাইভ HTTPS লিংক দিয়ে দেবে (যেমন: `https://bazarkinun-web.onrender.com`)*।

---

## ধাপ ৪: UptimeRobot দিয়ে সাইট সবসময় সজাগ রাখা (১ মিনিট)

রেন্ডারের ফ্রি সার্ভার ১৫ মিনিট কোনো ভিজিটর না পেলে স্লিপে চলে যায়। এটি চিরতরে বন্ধ করতে:

1. [uptimerobot.com](https://uptimerobot.com/)-এ গিয়ে একটি ফ্রি অ্যাকাউন্ট খুলুন।
2. **Add New Monitor**-এ ক্লিক করুন:
   - **Monitor Type:** `HTTP(s)`
   - **Friendly Name:** `BazarKinun Web`
   - **URL (or IP):** আপনার রেন্ডার সাইটের লিংক (যেমন: `https://bazarkinun-web.onrender.com`)
   - **Monitoring Interval:** `Every 5 minutes`
3. **Create Monitor** চাপুন।

এখন UptimeRobot প্রতি ৫ মিনিটে আপনার সাইটে অটোমেটিক ভিজিট পাঠাবে, ফলে আপনার সাইট **২৪ ঘণ্টা ৭ দিন সবসময় অ্যাক্টিভ থাকবে এবং প্রথম ক্লিকেই সাথে সাথে ওপেন হবে!**

---

## 🎯 ফলাফল
আপনার সম্পূর্ণ ইকমার্স প্ল্যাটফর্ম:
- **TiDB Cloud**-এর হাই-স্পিড ক্লাউড ডাটাবেসের সাথে সংযুক্ত থাকবে।
- **Render.com**-এর সুরক্ষিত ডকার সার্ভারে চলবে।
- **httpSMS** দিয়ে ফ্রি এসএমএস ওটিপি যাবে।
- এবং আপনার মাসিক বা বাৎসরিক হোস্টিং খরচ হবে **সম্পূর্ণ ৳০ (জিরো টাকা)**!

