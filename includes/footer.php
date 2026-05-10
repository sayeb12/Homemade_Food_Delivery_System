<?php
$footerBasePath = isset($footerBasePath) ? rtrim((string) $footerBasePath, '/') : '.';
$footerTheme = isset($footerTheme) ? (string) $footerTheme : 'blue';
$footerThemeClass = $footerTheme === 'green' ? 'footer-theme-green' : 'footer-theme-blue';
?>
    <style>
        .footer.footer-rich {
            position: relative;
            color: #fff;
            padding: 58px 0 22px;
            overflow: hidden;
            width: 100%;
            margin-top: 44px;
            isolation: isolate;
            box-sizing: border-box;
        }

        .footer.footer-rich,
        .footer.footer-rich * {
            box-sizing: border-box;
        }

        .footer.footer-rich .container {
            max-width: 1240px;
            margin: 0 auto;
            padding: 0 24px;
        }

        .footer-rich.footer-theme-blue {
            background:
                linear-gradient(rgba(8, 26, 52, 0.84), rgba(19, 49, 92, 0.92)),
                url('<?php echo $footerBasePath; ?>/assets/images/hilsha.jfif') left bottom / 180px auto no-repeat,
                url('<?php echo $footerBasePath; ?>/assets/images/morgi.jfif') right bottom / 190px auto no-repeat,
                radial-gradient(circle at top left, rgba(255, 215, 0, 0.12), transparent 32%),
                linear-gradient(135deg, #081A34, #13315C 58%, #214E7A);
        }

        .footer-rich.footer-theme-green {
            background:
                linear-gradient(rgba(27, 94, 32, 0.82), rgba(76, 175, 80, 0.9)),
                url('<?php echo $footerBasePath; ?>/assets/images/aloBorta.jfif') left bottom / 170px auto no-repeat,
                url('<?php echo $footerBasePath; ?>/assets/images/hilsha.jfif') right bottom / 180px auto no-repeat,
                radial-gradient(circle at top left, rgba(198, 255, 107, 0.14), transparent 32%),
                linear-gradient(135deg, #1b5e20, #2e7d32 58%, #4caf50);
        }

        .footer-rich-container {
            position: relative;
            z-index: 1;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: minmax(320px, 1.2fr) minmax(0, 1fr);
            gap: 18px;
            align-items: start;
        }

        .footer-links-panel {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            align-items: start;
        }

        .footer-eyebrow {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            font-size: 0.82rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 16px;
        }

        .footer-title {
            font-size: clamp(1.4rem, 2vw, 1.8rem);
            line-height: 1.15;
            margin: 0;
            color: #fff;
        }

        .footer-description {
            color: rgba(255, 255, 255, 0.82);
            max-width: 400px;
            margin: 6px 0 10px;
            font-size: 0.88rem;
        }

        .footer-food-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .footer-food-tags span {
            padding: 5px 10px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.12);
            font-size: 0.74rem;
        }

        .footer-column h3 {
            font-size: 1rem;
            margin: 0 0 10px;
            color: #fff;
        }

        .footer-column ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: 5px;
        }

        .footer-column a,
        .footer-contact-list li {
            color: rgba(255, 255, 255, 0.82);
            text-decoration: none;
            transition: color 0.2s ease;
            font-size: 0.92rem;
        }

        .footer-column li:nth-child(n+4) {
            display: none;
        }

        .footer-column a:hover,
        .footer-mini-links a:hover {
            color: #ffd54f;
        }

        .footer-contact-list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .footer-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            flex: 0 0 20px;
            color: #fff3b0;
            margin-top: 1px;
            font-style: normal;
        }

        .footer-social-icons {
            margin-top: 12px;
            display: flex;
            gap: 10px;
        }

        .footer-social-icons a {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            text-decoration: none;
            transition: all 0.25s ease;
            font-weight: 700;
            font-size: 0.8rem;
        }

        .footer-social-icons a:hover {
            background: #fff;
            color: #17304F;
            transform: translateY(-2px);
        }

        .footer-social-glyph {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            line-height: 1;
        }

        .footer-animation-strip {
            position: relative;
            margin: 18px 0 14px;
            height: 70px;
            border-radius: 26px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            overflow: hidden;
        }

        .footer-road {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 16px;
            height: 4px;
            background: repeating-linear-gradient(90deg, rgba(255, 255, 255, 0.65) 0 36px, transparent 36px 62px);
        }

        .footer-food-marquee {
            position: absolute;
            top: 9px;
            left: 0;
            display: flex;
            gap: 16px;
            white-space: nowrap;
            animation: footerFloatFoods 15s linear infinite;
        }

        .footer-food-marquee span {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
            font-size: 0.78rem;
        }

        .delivery-scooter {
            position: absolute;
            left: -220px;
            bottom: 6px;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.14);
            color: #fff7d6;
            animation: footerRideAcross 12s linear infinite;
            font-size: 0.8rem;
        }

        .delivery-scooter-icon {
            font-size: 1.4rem;
            color: #ffe082;
            line-height: 1;
        }

        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            flex-wrap: wrap;
            color: rgba(255, 255, 255, 0.78);
            font-size: 0.84rem;
        }

        .footer-mini-links {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
        }

        .footer-mini-links a {
            color: rgba(255, 255, 255, 0.78);
            text-decoration: none;
        }

        @keyframes footerRideAcross {
            0% { transform: translateX(0); }
            100% { transform: translateX(calc(100vw + 320px)); }
        }

        @keyframes footerFloatFoods {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-110%); }
        }

        @media (max-width: 900px) {
            .footer-grid {
                grid-template-columns: 1fr;
            }

            .footer-links-panel {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 700px) {
            .footer.footer-rich {
                padding: 44px 0 20px;
                margin-top: 34px;
            }

            .footer-bottom {
                flex-direction: column;
                align-items: flex-start;
            }

            .footer-links-panel {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .footer-animation-strip {
                height: 72px;
            }

            .delivery-scooter span {
                display: none;
            }
        }

        @media (max-width: 520px) {
            .footer.footer-rich .container {
                padding: 0 16px;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: 24px;
            }

            .footer-title {
                font-size: 1.25rem;
            }

            .footer-food-tags,
            .footer-mini-links,
            .footer-social-icons {
                gap: 10px;
            }

            .footer-food-marquee {
                top: 14px;
                gap: 10px;
            }

            .footer-food-marquee span {
                padding: 8px 12px;
                font-size: 0.82rem;
            }
        }
    </style>

    <!-- Footer -->
    <footer class="footer footer-rich <?php echo $footerThemeClass; ?>">
        <div class="container footer-rich-container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <span class="footer-eyebrow">Homemade Bengali Delivery</span>
                    <h2 class="footer-title">Warm homemade meals, delivered with care.</h2>
                    <p class="footer-description">
                        Bengali comfort food from trusted home chefs.
                    </p>
                    <div class="footer-food-tags">
                        <span>Kacchi</span>
                        <span>Hilsha</span>
                        <span>Bhorta</span>
                        <span>Morog Polao</span>
                    </div>
                </div>

                <div class="footer-column">
                    <h3>Explore</h3>
                    <ul>
                        <li><a href="<?php echo $footerBasePath; ?>/index.php#top-selling-section">Top Selling Meals</a></li>
                        <li><a href="<?php echo $footerBasePath; ?>/index.php?search_in=category&search=veg#top-selling-section">Vegetarian Picks</a></li>
                        <li><a href="<?php echo $footerBasePath; ?>/index.php?search_in=category&search=nonveg#top-selling-section">Chef Specials</a></li>
                        <li><a href="<?php echo $footerBasePath; ?>/customer/login.php">Order as Customer</a></li>
                    </ul>
                </div>

                <div class="footer-column">
                    <h3>For Partners</h3>
                    <ul>
                        <li><a href="<?php echo $footerBasePath; ?>/chef/login.php">Chef Login</a></li>
                        <li><a href="<?php echo $footerBasePath; ?>/chef/register.php">Become a Home Chef</a></li>
                        <li><a href="<?php echo $footerBasePath; ?>/customer/register.php">Create Customer Account</a></li>
                        <li><a href="<?php echo $footerBasePath; ?>/index.php#testimonial-section">Customer Reviews</a></li>
                    </ul>
                </div>

                <div class="footer-column">
                    <h3>Support</h3>
                    <ul class="footer-contact-list">
                        <li><span class="footer-icon">☎</span><span>+880 1984 745679</span></li>
                        <li><span class="footer-icon">✉</span><span>support@homemadefood.local</span></li>
                        <li><span class="footer-icon">⌂</span><span>Serving Dhaka and nearby areas</span></li>
                        <li><span class="footer-icon">⏰</span><span>Open daily from 8:00 AM to 11:00 PM</span></li>
                    </ul>
                    <div class="social-icons footer-social-icons">
                        <a href="#" aria-label="Facebook"><span class="footer-social-glyph">f</span></a>
                        <a href="#" aria-label="Instagram"><span class="footer-social-glyph">ig</span></a>
                        <a href="#" aria-label="Twitter"><span class="footer-social-glyph">x</span></a>
                        <a href="#" aria-label="Youtube"><span class="footer-social-glyph">▶</span></a>
                    </div>
                </div>
            </div>

            <div class="footer-animation-strip" aria-hidden="true">
                <div class="footer-road"></div>
                <div class="footer-food-marquee">
                    <span>🥟 Pitha</span>
                    <span>🍲 Khichuri</span>
                    <span>🥙 Fuchka</span>
                    <span>🐟 Shorshe Ilish</span>
                </div>
                <div class="delivery-scooter">
                    <span class="delivery-scooter-icon">🛵</span>
                    <span>Fresh delivery on the way</span>
                </div>
            </div>

            <div class="footer-bottom">
                <p>Cooked by local hands. Delivered with heart.</p>
                <div class="footer-mini-links">
                    <a href="#">Privacy</a>
                    <a href="#">Terms</a>
                    <a href="#">Help Center</a>
                </div>
            </div>
        </div>
    </footer>
    
</body>
</html>
