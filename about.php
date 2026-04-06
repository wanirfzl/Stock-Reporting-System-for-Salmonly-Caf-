<?php
// about.php
define('ACCESS_ALLOWED', true);
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salmonly Café - About Us</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@300;400;500;600;700;800&family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Instrument Sans', sans-serif;
            background-color: #F2E8DF;
            color: #3C2A21;
            min-height: 100vh;
        }

        .grain-bg::before {
            content: "";
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIzMDAiIGhlaWdodD0iMzAwIj48ZmlsdGVyIGlkPSJmIj48ZmVUdXJidWxlbmNlIHR5cGU9ImZyYWN0YWxOb2lzZSIgYmFzZUZyZXF1ZW5jeT0iLjc0IiBudW1PY3RhdmVzPSIzIiAvPjwvZmlsdGVyPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbHRlcj0idXJsKCNmKSIgb3BhY2l0eT0iMC4xNSIgLz48L3N2Zz4=');
            opacity: 0.1;
            pointer-events: none;
            z-index: 1;
        }

        .hero-section {
            background: linear-gradient(135deg, #8B5A2B 0%, #5D3A1A 100%);
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            pointer-events: none;
        }

        .value-card {
            transition: all 0.3s ease;
            background: white;
            border-radius: 1.5rem;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05);
        }

        .value-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px -10px rgba(139, 90, 43, 0.2);
        }

        .team-card {
            transition: all 0.3s ease;
            background: white;
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05);
        }

        .team-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px -10px rgba(139, 90, 43, 0.2);
        }

        .gallery-img {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .gallery-img:hover {
            transform: scale(1.05);
            box-shadow: 0 20px 30px -10px rgba(0, 0, 0, 0.2);
        }

        .btn-login {
            background: linear-gradient(135deg, #8B5A2B 0%, #B07A4A 100%);
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(139, 90, 43, 0.4);
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            border: 1px solid rgba(139, 90, 43, 0.2);
            background: white;
            color: #3C2A21;
        }

        .nav-link:hover {
            background: #8B5A2B;
            color: white;
            border-color: #8B5A2B;
            transform: translateY(-2px);
        }

        .nav-link.login-btn {
            background: #8B5A2B;
            color: white;
            border: none;
        }

        .nav-link.login-btn:hover {
            background: #B07A4A;
        }
    </style>
</head>
<body class="grain-bg">
    
    <!-- Navigation -->
    <nav class="flex justify-end items-center gap-4 px-6 md:px-10 py-5 max-w-7xl mx-auto">
        <a href="login.php" class="nav-link login-btn">
            <span class="material-symbols-outlined">login</span>
            Login
        </a>
        <a href="register.php" class="nav-link">
            <span class="material-symbols-outlined">app_registration</span>
            Register
        </a>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section text-white py-20 md:py-28 relative">
        <div class="max-w-7xl mx-auto px-6 md:px-10 text-center relative z-10">
            <div class="mb-6">
                <img src="images/logo.png" alt="Salmonly Café Logo" class="w-24 h-24 mx-auto rounded-2xl shadow-lg" style="background: white; object-fit: cover;">
            </div>
            <h1 class="text-5xl md:text-7xl font-black mb-4">Salmonly Café</h1>
            <p class="text-xl md:text-2xl text-white/90 max-w-2xl mx-auto">Yummy in Tummy</p>
            <div class="mt-8 flex gap-4 justify-center">
                <span class="px-4 py-2 bg-white/20 rounded-full text-sm">Est. 2020</span>
                <span class="px-4 py-2 bg-white/20 rounded-full text-sm">📍 Kuala Lumpur</span>
            </div>
        </div>
    </section>

    <!-- Our Story -->
    <section class="py-16 md:py-20 max-w-7xl mx-auto px-6 md:px-10">
        <div class="grid md:grid-cols-2 gap-12 items-center">
            <div>
                <span class="text-[#8B5A2B] text-sm font-bold uppercase tracking-wider">Our Story</span>
                <h2 class="text-3xl md:text-4xl font-black text-[#3C2A21] mt-2 mb-6">Serving Happiness Since 2020</h2>
                <p class="text-[#3C2A21]/70 leading-relaxed mb-4">
                    At Salmonly Cafe, our journey began with a deep love for salmon and a simple idea—to create a cozy space where people can enjoy comforting food with a modern twist. We were inspired by the growing café culture and wanted to bring something unique to the table, focusing on fresh ingredients, bold flavors, and especially our signature mentai creations that quickly became a favorite among our customers.
                </p>
                <p class="text-[#3C2A21]/70 leading-relaxed mb-6">
                    From a small dream, Salmonly Cafe has grown into a welcoming spot where friends and family gather to relax, share good food, and create meaningful memories. Every dish we serve is prepared with care and passion, reflecting our commitment to quality and creativity. More than just a café, Salmonly Cafe is a place where great taste and warm moments come together.
                </p>
                <div class="flex gap-4">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#8B5A2B]">coffee</span>
                        <span class="text-sm font-medium">Freshly Brewed</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#8B5A2B]">restaurant</span>
                        <span class="text-sm font-medium">Locally Sourced</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#8B5A2B]">favorite</span>
                        <span class="text-sm font-medium">Made with Love</span>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <img src="images/story1.jpeg" alt="Cafe Interior" class="rounded-2xl shadow-lg w-full h-64 object-cover gallery-img">
                <img src="images/story2.jpeg" alt="Coffee Art" class="rounded-2xl shadow-lg w-full h-64 object-cover gallery-img mt-8">
                <img src="images/story3.jpeg" alt="Pastries" class="rounded-2xl shadow-lg w-full h-64 object-cover gallery-img -mt-4">
                <img src="images/interior1.jpeg" alt="Salmon Dish" class="rounded-2xl shadow-lg w-full h-64 object-cover gallery-img">
            </div>
        </div>
    </section>

    <!-- Our Values -->
    <section class="bg-[#FAF9F6] py-16 md:py-20">
        <div class="max-w-7xl mx-auto px-6 md:px-10">
            <div class="text-center mb-12">
                <span class="text-[#8B5A2B] text-sm font-bold uppercase tracking-wider">What We Believe</span>
                <h2 class="text-3xl md:text-4xl font-black text-[#3C2A21] mt-2">Our Core Values</h2>
                <p class="text-[#3C2A21]/60 mt-4 max-w-2xl mx-auto">The principles that guide everything we do</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="value-card">
                    <div class="w-16 h-16 bg-[#8B5A2B]/10 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <span class="material-symbols-outlined text-3xl text-[#8B5A2B]">coffee</span>
                    </div>
                    <h3 class="text-xl font-bold text-[#3C2A21] mb-2">Quality First</h3>
                    <p class="text-[#3C2A21]/60">We never compromise on quality. From our coffee beans to our ingredients, we choose only the best.</p>
                </div>
                <div class="value-card">
                    <div class="w-16 h-16 bg-[#8B5A2B]/10 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <span class="material-symbols-outlined text-3xl text-[#8B5A2B]">diversity_3</span>
                    </div>
                    <h3 class="text-xl font-bold text-[#3C2A21] mb-2">Community</h3>
                    <p class="text-[#3C2A21]/60">We believe in creating a space where everyone feels welcome, whether you're a regular or a first-time visitor.</p>
                </div>
                <div class="value-card">
                    <div class="w-16 h-16 bg-[#8B5A2B]/10 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <span class="material-symbols-outlined text-3xl text-[#8B5A2B]">eco</span>
                    </div>
                    <h3 class="text-xl font-bold text-[#3C2A21] mb-2">Sustainability</h3>
                    <p class="text-[#3C2A21]/60">We are committed to reducing waste and supporting local farmers and producers.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Menu Highlights -->
    <section class="py-16 md:py-20 max-w-7xl mx-auto px-6 md:px-10">
        <div class="text-center mb-12">
            <span class="text-[#8B5A2B] text-sm font-bold uppercase tracking-wider">Signature Dishes</span>
            <h2 class="text-3xl md:text-4xl font-black text-[#3C2A21] mt-2">Our Specialties</h2>
            <p class="text-[#3C2A21]/60 mt-4 max-w-2xl mx-auto">From our kitchen to your plate, made fresh daily</p>
        </div>
        <div class="grid md:grid-cols-3 gap-6">
            <div class="bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-xl transition-all">
                <img src="images/bestseller1.jpeg" alt="Salmon Dish" class="w-full h-56 object-cover">
                <div class="p-6">
                    <h3 class="text-xl font-bold text-[#3C2A21]">Burnt Salmon Mentai</h3>
                    <p class="text-[#3C2A21]/60 mt-2">Sushi rice, Seaweed flakes, Salmon Chunks, Mentai sauce</p>
                    <div class="mt-4 flex justify-between items-center">
                        <span class="text-[#8B5A2B] font-bold">RM 20.00</span>
                        <span class="text-xs bg-[#F2E8DF] px-3 py-1 rounded-full">Best Seller</span>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-xl transition-all">
                <img src="images/bestseller2.jpeg" alt="Coffee" class="w-full h-56 object-cover">
                <div class="p-6">
                    <h3 class="text-xl font-bold text-[#3C2A21]">Mentai Dimsum</h3>
                    <p class="text-[#3C2A21]/60 mt-2">Steamed dim sum topped with creamy mentai sauce</p>
                    <div class="mt-4 flex justify-between items-center">
                        <span class="text-[#8B5A2B] font-bold">RM 15.00</span>
                        <span class="text-xs bg-[#F2E8DF] px-3 py-1 rounded-full">Customer Favorite</span>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-xl transition-all">
                <img src="images/bestseller3.jpeg" alt="Pastries" class="w-full h-56 object-cover">
                <div class="p-6">
                    <h3 class="text-xl font-bold text-[#3C2A21]">Loaded Fries with Mentai Drizzle</h3>
                    <p class="text-[#3C2A21]/60 mt-2">Crispy fries topped with creamy, slightly spicy mentai sauce for a rich and flavorful bite.</p>
                    <div class="mt-4 flex justify-between items-center">
                        <span class="text-[#8B5A2B] font-bold">RM 13.00</span>
                        <span class="text-xs bg-[#F2E8DF] px-3 py-1 rounded-full">Perfect Pairing</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

        <!-- Our Team -->
    <section class="bg-[#FAF9F6] py-16 md:py-20">
        <div class="max-w-7xl mx-auto px-6 md:px-10">
            <div class="text-center mb-12">
                <span class="text-[#8B5A2B] text-sm font-bold uppercase tracking-wider">Meet Our Team</span>
                <h2 class="text-3xl md:text-4xl font-black text-[#3C2A21] mt-2">The People Behind the Magic</h2>
                <p class="text-[#3C2A21]/60 mt-4 max-w-2xl mx-auto">Passionate owners and team who make every visit special</p>
            </div>
            <div class="grid md:grid-cols-2 gap-8 max-w-3xl mx-auto">
                <div class="team-card text-center">
                    <img src="images/kaksilla.jpeg" alt="Boss" class="w-full h-64 object-cover">
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-[#3C2A21]">Marsilla Ismail</h3>
                        <p class="text-[#8B5A2B] text-sm">Owner</p>
                        <p class="text-[#3C2A21]/60 text-sm mt-2">10 years of culinary experience, specializes in fusion cuisine</p>
                    </div>
                </div>
                <div class="team-card text-center">
                    <img src="images/kaksofia.jpeg" alt="Boss" class="w-full h-64 object-cover">
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-[#3C2A21]">Sofia Ismail</h3>
                        <p class="text-[#8B5A2B] text-sm">Owner</p>
                        <p class="text-[#3C2A21]/60 text-sm mt-2">Award-winning latte artist, coffee enthusiast</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Gallery Section -->
    <section class="py-16 md:py-20 max-w-7xl mx-auto px-6 md:px-10">
        <div class="text-center mb-12">
            <span class="text-[#8B5A2B] text-sm font-bold uppercase tracking-wider">Moments Captured</span>
            <h2 class="text-3xl md:text-4xl font-black text-[#3C2A21] mt-2">Our Gallery</h2>
            <p class="text-[#3C2A21]/60 mt-4">Take a peek into our delicious creations</p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <img src="images/gallery1.jpeg" alt="Cafe Interior" class="rounded-2xl w-full h-48 object-cover gallery-img">
            <img src="images/gallery2.jpeg" alt="Coffee Art" class="rounded-2xl w-full h-48 object-cover gallery-img">
            <img src="images/gallery3.jpeg" alt="Pastries" class="rounded-2xl w-full h-48 object-cover gallery-img">
            <img src="images/gallery4.jpeg" alt="Salmon Dish" class="rounded-2xl w-full h-48 object-cover gallery-img">
            <img src="images/gallery6.jpeg" alt="Food" class="rounded-2xl w-full h-48 object-cover gallery-img">
            <img src="images/gallery7.jpeg" alt="Coffee" class="rounded-2xl w-full h-48 object-cover gallery-img">
            <img src="images/gallery8.jpeg" alt="Croissant" class="rounded-2xl w-full h-48 object-cover gallery-img">
            <img src="images/gallery9.jpeg" alt="Interior" class="rounded-2xl w-full h-48 object-cover gallery-img">
        </div>
    </section>

        <!-- Location & Hours -->
    <section class="bg-[#FAF9F6] py-16 md:py-20">
        <div class="max-w-7xl mx-auto px-6 md:px-10">
            <div class="grid md:grid-cols-2 gap-12">
                <div>
                    <span class="text-[#8B5A2B] text-sm font-bold uppercase tracking-wider">Visit Us</span>
                    <h2 class="text-3xl md:text-4xl font-black text-[#3C2A21] mt-2 mb-6">Location & Hours</h2>
                    <div class="space-y-4">
                        <div class="flex gap-3 items-start">
                            <span class="material-symbols-outlined text-[#8B5A2B]">location_on</span>
                            <div>
                                <p class="font-bold">Salmonly Café</p>
                                <p class="text-[#3C2A21]/60">49, Jalan Dwitasik, Bandar Sri Permaisuri, 55000 Kuala Lumpur, Wilayah Persekutuan Kuala Lumpur</p>
                            </div>
                        </div>
                        <div class="flex gap-3 items-start">
                            <span class="material-symbols-outlined text-[#8B5A2B]">schedule</span>
                            <div>
                                <p class="font-bold">Opening Hours</p>
                                <p class="text-[#3C2A21]/60">Monday - Thursday: 9:30 AM - 9:30 PM</p>
                                <p class="text-[#3C2A21]/60">Friday - Sunday: 11:00 AM - 11:00 PM</p>
                            </div>
                        </div>
                        <div class="flex gap-3 items-start">
                            <span class="material-symbols-outlined text-[#8B5A2B]">call</span>
                            <div>
                                <p class="font-bold">Contact</p>
                                <p class="text-[#3C2A21]/60">011-2082 1742</p>
                                <p class="text-[#3C2A21]/60">Salmonly.inc@gmail.com</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-8 flex gap-4">
                        <a href="https://www.instagram.com/salmonlycafe?igsh=dWh2bDdxeGp4cmZq" target="_blank" class="p-3 bg-white rounded-full shadow-md hover:bg-[#8B5A2B] hover:text-white transition-colors group">
                            <i class="fab fa-instagram text-2xl text-[#3C2A21] group-hover:text-white"></i>
                        </a>
                        <a href="https://www.facebook.com/salmonlyhq/" target="_blank" class="p-3 bg-white rounded-full shadow-md hover:bg-[#8B5A2B] hover:text-white transition-colors group">
                            <i class="fab fa-facebook-f text-2xl text-[#3C2A21] group-hover:text-white"></i>
                        </a>
                        <a href="https://www.tiktok.com/@salmonlycafe?_r=1&_t=ZS-957JlvYeHWE" target="_blank" class="p-3 bg-white rounded-full shadow-md hover:bg-[#8B5A2B] hover:text-white transition-colors group">
                             <i class="fab fa-tiktok text-2xl text-[#3C2A21] group-hover:text-white"></i>
                        </a>
                    </div>
                </div>
                <div>
                    <div class="bg-white rounded-2xl p-6 shadow-lg">
                        <h3 class="text-xl font-bold text-[#3C2A21] mb-4">Find Us Here</h3>
                        <div class="rounded-xl overflow-hidden">
                            <!-- Google Maps Embed - Boleh klik dan akan buka Google Maps -->
                            <iframe 
                                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3983.8056768097264!2d101.586491!3d3.073055!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31cc4e3c2b5f8b8f%3A0x5c2b5f8b8f5c2b5f!2sSS15%20Subang%20Jaya!5e0!3m2!1sen!2smy!4v1700000000000!5m2!1sen!2smy" 
                                width="100%" 
                                height="250" 
                                style="border:0;" 
                                allowfullscreen="" 
                                loading="lazy" 
                                referrerpolicy="no-referrer-when-downgrade">
                            </iframe>
                        </div>
                        <div class="mt-4 text-center">
                            <a href="https://www.google.com/maps/dir//Salmonly+Cafe,+49,+Jalan+Dwitasik,+Bandar+Sri+Permaisuri,+55000+Kuala+Lumpur,+Federal+Territory+of+Kuala+Lumpur,+Malaysia/@3.1166471,101.7170433,14z/data=!4m8!4m7!1m0!1m5!1m1!1s0x31cc43d3cb88d99b:0x8d7e0dbb473c6e3d!2m2!1d101.7134469!2d3.1015858?entry=ttu&g_ep=EgoyMDI2MDMyNC4wIKXMDSoASAFQAw%3D%3D" target="_blank" 
                               class="inline-flex items-center gap-2 px-6 py-3 bg-[#8B5A2B] text-white rounded-full text-sm font-bold hover:bg-[#B07A4A] transition-colors">
                                <span class="material-symbols-outlined text-lg">directions</span>
                                Open in Google Maps
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-[#3C2A21] text-white py-12">
        <div class="max-w-7xl mx-auto px-6 md:px-10 text-center">
            <img src="images/logo.png" alt="Salmonly Café Logo" class="w-16 h-16 mx-auto rounded-xl mb-4" style="background: white; object-fit: cover;">
            <p class="text-white/70 text-sm">© <?php echo date('Y'); ?> Salmonly Café. All rights reserved.</p>
            <p class="text-white/50 text-xs mt-2">Quality in every dish, precision in every stock</p>
        </div>
    </footer>

    <script>
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
    </script>
</body>
</html>
