<?php
$isHomepageHeader = true;
$pageTitle = 'Home';
require_once 'includes/header.php';
?>

<style>
/* Reset and Base Styles */
* {

    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', sans-serif;
    --page-accent: #ffb703;
    --page-secondary: #4b6fef;
    --page-wash: rgba(255, 183, 3, 0.12);
    background:
        radial-gradient(circle at top left, var(--page-wash), transparent 32%),
        radial-gradient(circle at bottom right, rgba(75, 111, 239, 0.12), transparent 28%),
        #E6ECF4;
    color: #1A2A44;
    line-height: 1.7;
    overflow-x: hidden;
    transition: background 0.5s ease, color 0.5s ease;
}

/* Container */
.container {
    max-width: 1240px;
    margin: 0 auto;
    padding: 0 24px;
}

/* Hero Section */
.hero {
    background: linear-gradient(135deg, #1C315E 0%, #4B6FEF 100%);
    color: #FFFFFF;
    padding: 120px 0;
    text-align: center;
    position: relative;
    border-bottom: 6px solid #3B5ACF;
}

.hero h1 {
    font-size: 3.5rem;
    font-weight: 800;
    margin-bottom: 1.2rem;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    color: #FFFFFF; /* Explicitly set to white */
}

.hero p {
    font-size: 1.4rem;
    margin-bottom: 2.5rem;
    opacity: 0.95;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
    color: #FFFFFF; /* Explicitly set to white */
}

.hero .btn {
    display: inline-block;
    background-color: #FFD700;
    color: #1A2A44;
    padding: 14px 32px;
    font-size: 1.2rem;
    font-weight: 700;
    text-decoration: none;
    border-radius: 10px;
    transition: background-color 0.3s, transform 0.2s, box-shadow 0.3s;
    margin: 0 12px;
}

.hero .btn:hover {
    background-color: #FFC107;
    transform: translateY(-3px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.hero .btn.secondary {
    background-color: transparent;
    border: 2px solid #FFFFFF;
    color: #FFFFFF;
}

.hero .btn.secondary:hover {
    background-color: #FFFFFF;
    color: #1A2A44;
    border-color: #FFFFFF;
}

/* Food Animation in Top-Right */
.food-animation {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 150px;
    height: 150px;
    overflow: hidden;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    z-index: 10;
}

.food-animation img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    position: absolute;
    top: 0;
    left: 0;
    opacity: 0;
    animation: fade 9s infinite;
}

.food-animation img:nth-child(1) { animation-delay: 0s; }
.food-animation img:nth-child(2) { animation-delay: 3s; }
.food-animation img:nth-child(3) { animation-delay: 6s; }

@keyframes fade {
    0% { opacity: 0; }
    11.11% { opacity: 1; }
    33.33% { opacity: 1; }
    44.44% { opacity: 0; }
    100% { opacity: 0; }
}

/* Section Styles */
.section {
    padding: 80px 0;
    background-color: #FFFFFF;
    border-radius: 16px;
    margin: 50px 0;
    box-shadow: 0 6px 25px rgba(0, 0, 0, 0.08);
    position: relative;
    overflow: hidden;
}

/* Add a subtle background texture to sections */
.section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAAAXNSR0IArs4c6QAAAB9JREFUeF7tzrENgCAMQ9G49x+0t7e30J4eUvsG8QUc5dIAvOENhAAAAAElFTkSuQmCC') repeat;
    opacity: 0.05;
    z-index: 0;
}

.section > * {
    position: relative;
    z-index: 1;
}

.section-title, .st {
    font-size: 2.5rem;
    font-weight: 800;
    color: #1A2A44;
    text-align: center;
    margin-bottom: 48px;
    position: relative;
}

.section-title::after, .st::after {
    content: '';
    width: 80px;
    height: 4px;
    background: linear-gradient(90deg, #FFD700, #FFC107);
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    border-radius: 2px;
}

/* Food Grid */
.food-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 280px), 1fr));
    gap: clamp(20px, 2.4vw, 32px);
}

/* Enhanced Card Styles for Top Selling Items */
.card {
    --card-hue: 40; /* Base hue for color variation */
    background: linear-gradient(145deg, #FFFFFF, #F9FBFF);
    border-radius: 20px;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease, rotate 0.3s ease;
    border: 1px solid #DDE4EE;
    position: relative;
    animation: slideUp 0.8s ease-out forwards;
    display: grid;
    grid-template-rows: auto 1fr;
    min-height: clamp(420px, 44vw, 560px);
}

/* Color variation for each card */
.card:nth-child(1) { --card-hue: 0; }   /* Red */
.card:nth-child(2) { --card-hue: 40; }  /* Orange */
.card:nth-child(3) { --card-hue: 90; }  /* Green */
.card:nth-child(4) { --card-hue: 200; } /* Cyan */
.card:nth-child(5) { --card-hue: 270; } /* Purple */
.card:nth-child(6) { --card-hue: 320; } /* Pink */

.card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 5px;
    background: linear-gradient(90deg, hsl(var(--card-hue), 80%, 60%), hsl(var(--card-hue), 80%, 50%));
    transition: height 0.3s ease;
}

.card:hover {
    transform: translateY(-12px) scale(1.02);
    box-shadow: 0 22px 45px rgba(15, 35, 68, 0.18);
}

.card:hover::before {
    height: 100%;
    opacity: 0.05;
}

/* Colorful Placeholder Design */
.card-placeholder {
    position: relative;
    height: 240px;
    background: linear-gradient(135deg, hsl(var(--card-hue), 80%, 60%), hsl(var(--card-hue), 80%, 40%));
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    animation: pulseGlow 2s infinite ease-in-out;
}

.card-placeholder::after {
    content: '🍽️';
    font-size: 5rem;
    opacity: 0.3;
    position: absolute;
    color: #FFFFFF;
    text-shadow: 0 3px 6px rgba(0, 0, 0, 0.3);
}

.card-placeholder::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.3), rgba(0, 0, 0, 0.2));
    pointer-events: none;
}

/* Pulsing Glow Animation for Placeholder */
@keyframes pulseGlow {
    0% { box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
    50% { box-shadow: 0 0 20px hsl(var(--card-hue), 80%, 50%); }
    100% { box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
}

.card-media {
    position: relative;
    aspect-ratio: 4 / 3;
    overflow: hidden;
    background: linear-gradient(135deg, hsl(var(--card-hue), 70%, 88%), hsl(var(--card-hue), 70%, 78%));
}

.card-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.45s ease, filter 0.45s ease;
}

.card:hover .card-image {
    transform: scale(1.09);
    filter: saturate(1.08) contrast(1.04);
}

.card-media::after {
    content: '';
    position: absolute;
    inset: 0;
    background:
        linear-gradient(to top, rgba(10, 24, 45, 0.52), rgba(10, 24, 45, 0.02) 55%),
        linear-gradient(120deg, rgba(255, 255, 255, 0.18), rgba(255, 255, 255, 0) 38%);
    pointer-events: none;
}

.card-media::before {
    content: '';
    position: absolute;
    inset: -120% 30% auto -30%;
    height: 280%;
    background: linear-gradient(115deg, transparent 35%, rgba(255, 255, 255, 0.36) 50%, transparent 65%);
    transform: translateX(-120%) rotate(10deg);
    transition: transform 0.6s ease;
    pointer-events: none;
}

.card:hover .card-media::before {
    transform: translateX(120%) rotate(10deg);
}

.card-badge {
    position: absolute;
    left: 18px;
    bottom: 18px;
    z-index: 1;
    padding: 8px 14px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.92);
    color: #1A2A44;
    font-size: 0.92rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    box-shadow: 0 8px 18px rgba(0, 0, 0, 0.12);
}

.card-content {
    padding: clamp(22px, 2.4vw, 32px);
    background: rgba(255, 255, 255, 0.95);
    text-align: left;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.food-name {
    font-size: 1.9rem;
    font-weight: 800;
    color: #1A2A44;
    margin-bottom: 14px;
    text-transform: capitalize;
    display: flex;
    align-items: center;
    gap: 10px;
    letter-spacing: 0.5px;
}

.food-name::before {
    content: '🍴';
    font-size: 1.3rem;
    color: hsl(var(--card-hue), 80%, 50%);
}

.chef-info, .location-info, .cuisine {
    font-size: 1.15rem;
    color: #5A6A88;
    font-weight: 500;
    background: #E6ECF4;
    padding: 6px 14px;
    border-radius: 12px;
    display: inline-block;
    margin: 4px 0;
    transition: background-color 0.3s;
}

.chef-info:hover, .location-info:hover, .cuisine:hover {
    background-color: #DDE4EE;
}

.cuisine-rating {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 16px 0;
    gap: 12px;
    flex-wrap: wrap;
}

.rating {
    display: flex;
    align-items: center;
    gap: 8px;
    justify-content: flex-end;
}

.star {
    color: #FFB300;
    font-size: 1.4rem;
    animation: pulseStar 1.5s infinite;
}

@keyframes pulseStar {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

.rating-value {
    font-size: 1.15rem;
    font-weight: 700;
    color: #1A2A44;
}

.card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 18px;
    border-top: 1px solid #DDE4EE;
    margin-top: auto;
    gap: 14px;
    flex-wrap: wrap;
}

.time, .price {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 1.1rem;
    color: #5A6A88;
    font-weight: 500;
}

header {
    position: sticky;
    top: 0;
    z-index: 1000;
    background: rgba(12, 27, 52, 0.9);
    backdrop-filter: blur(16px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.header-container {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    gap: 20px;
    align-items: center;
    padding: 10px 0;
}

.logo a {
    display: inline-flex;
    align-items: center;
}

.hero_logo {
    height: 48px;
    width: auto;
    filter: drop-shadow(0 10px 18px rgba(0, 0, 0, 0.18));
}

.search-bar-home {
    display: grid;
    gap: 10px;
}

.search-form-home {
    display: grid;
    grid-template-columns: minmax(0, 1.6fr) 160px 140px;
    gap: 10px;
    padding: 8px;
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.14);
    box-shadow: 0 18px 34px rgba(7, 18, 36, 0.16);
}

.search-form-home input,
.search-form-home select {
    width: 100%;
    min-width: 0;
    height: 44px;
    border: none;
    border-radius: 14px;
    padding: 0 16px;
    font-size: 0.92rem;
    background: rgba(255, 255, 255, 0.92);
    color: #16304f;
    outline: none;
}

.search-form-home select {
    appearance: none;
    cursor: pointer;
}

.header-search-btn {
    height: 44px;
    border: none;
    border-radius: 14px;
    font-size: 0.92rem;
    font-weight: 700;
    color: #1A2A44;
    background: linear-gradient(135deg, #FFD700, #FFB703);
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.header-search-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px rgba(255, 183, 3, 0.22);
}

.homepage-nav-pills {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.homepage-nav-pills a {
    text-decoration: none;
    padding: 7px 12px;
    border-radius: 999px;
    color: #fff;
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.14);
    font-size: 0.82rem;
    transition: all 0.2s ease;
}

.homepage-nav-pills a:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-1px);
}

.user-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #fff;
}

.btn {
    padding: 9px 16px;
    font-size: 0.9rem;
}

.page-food-backdrop {
    position: fixed;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
    z-index: 0;
}

.floating-food {
    position: absolute;
    display: grid;
    place-items: center;
    border-radius: 30px;
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255, 255, 255, 0.22);
    box-shadow: 0 16px 34px rgba(26, 42, 68, 0.1);
    backdrop-filter: blur(10px);
    opacity: 0.2;
    transition: transform 0.18s linear, opacity 0.3s ease, filter 0.4s ease;
}

.floating-food img,
.floating-food span {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: inherit;
}

.floating-food.emoji-food {
    background: rgba(255, 255, 255, 0.08);
}

.floating-food.emoji-food span {
    display: grid;
    place-items: center;
    font-size: clamp(2rem, 4vw, 3.2rem);
    line-height: 1;
}

.floating-food.active {
    opacity: 0.32;
    filter: saturate(1.15);
}

.food-1 { top: 11%; left: -1%; width: 138px; height: 138px; }
.food-2 { top: 23%; right: 2%; width: 152px; height: 152px; }
.food-3 { top: 52%; left: 4%; width: 126px; height: 126px; }
.food-4 { top: 66%; right: 6%; width: 148px; height: 148px; }
.food-5 { top: 81%; left: 10%; width: 108px; height: 108px; }
.food-6 { top: 40%; right: 18%; width: 98px; height: 98px; }

.hero,
.section,
.testimonial-section,
.footer-rich {
    position: relative;
    z-index: 1;
}

#top-selling-section {
    background:
        linear-gradient(135deg, rgba(255, 255, 255, 0.94), rgba(245, 249, 255, 0.94)),
        url('assets/images/hilsha.jfif') top left / 220px auto no-repeat,
        url('assets/images/morgi.jfif') top right / 240px auto no-repeat,
        url('assets/images/aloBorta.jfif') bottom left / 210px auto no-repeat,
        url('assets/images/bhat.jfif') bottom right / 230px auto no-repeat;
    border: 1px solid rgba(255, 255, 255, 0.7);
}

#top-selling-section::before {
    background:
        linear-gradient(rgba(255, 255, 255, 0.72), rgba(255, 255, 255, 0.72)),
        url('assets/images/dish1.png') center center / 320px auto repeat;
    opacity: 0.08;
}

#top-selling-section .container {
    position: relative;
}

#top-selling-section .container::before,
#top-selling-section .container::after {
    content: '';
    position: absolute;
    width: 180px;
    height: 180px;
    border-radius: 28px;
    opacity: 0.16;
    z-index: 0;
    filter: saturate(1.05);
    box-shadow: 0 18px 30px rgba(26, 42, 68, 0.08);
}

#top-selling-section .container::before {
    top: 120px;
    left: -28px;
    background: url('assets/images/goru.jfif') center/cover no-repeat;
    transform: rotate(-8deg);
}

#top-selling-section .container::after {
    right: -24px;
    bottom: 36px;
    background: url('assets/images/aloBaja.jfif') center/cover no-repeat;
    transform: rotate(9deg);
}

#top-selling-section .section-intro,
#top-selling-section .food-grid,
#top-selling-section .spotlight-pagination {
    position: relative;
    z-index: 1;
}

.section-intro {
    display: flex;
    justify-content: space-between;
    align-items: end;
    gap: 24px;
    flex-wrap: wrap;
    margin-bottom: 36px;
    text-align: center;
    justify-content: center;
}

.section-copy {
    max-width: 720px;
    margin: 0 auto;
}

.section-kicker {
    display: inline-flex;
    align-items: center;
    padding: 8px 14px;
    border-radius: 999px;
    background: #FFF4CC;
    color: #A36700;
    font-size: 0.85rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    margin-bottom: 18px;
}

.section-subtitle {
    margin-top: 18px;
    font-size: 1.08rem;
    color: #5A6A88;
    max-width: 620px;
    margin-left: auto;
    margin-right: auto;
}

.spotlight-stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(150px, 1fr));
    gap: 14px;
}

.spotlight-stat {
    padding: 18px 20px;
    border-radius: 20px;
    background: linear-gradient(145deg, #F7FAFF, #FFFFFF);
    border: 1px solid #DDE4EE;
    box-shadow: 0 10px 24px rgba(26, 42, 68, 0.08);
}

.spotlight-stat strong {
    display: block;
    font-size: 1.7rem;
    color: #1A2A44;
}

.spotlight-stat span {
    display: block;
    margin-top: 6px;
    color: #5A6A88;
    font-size: 0.92rem;
}

.food-grid.spotlight-grid {
    grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
    gap: 24px;
    align-items: stretch;
}

.spotlight-grid .card {
    min-height: 0;
    background: rgba(255, 255, 255, 0.58);
    border: 1px solid rgba(255, 255, 255, 0.6);
    backdrop-filter: blur(18px);
    box-shadow: 0 20px 38px rgba(26, 42, 68, 0.12);
}

.spotlight-grid .card:hover {
    transform: translateY(-8px) scale(1.01);
}

.spotlight-grid .card-media {
    aspect-ratio: 16 / 10;
}

.spotlight-grid .card-content {
    padding: 22px;
    gap: 10px;
}

.spotlight-grid .food-name {
    font-size: 1.55rem;
    margin-bottom: 8px;
}

.spotlight-grid .card-footer {
    padding-top: 14px;
}

.spotlight-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 14px;
    margin-top: 28px;
}

.spotlight-page-indicator {
    color: #5A6A88;
    font-weight: 700;
}

.spotlight-arrow {
    width: 48px;
    height: 48px;
    border: none;
    border-radius: 50%;
    background: rgba(26, 42, 68, 0.9);
    color: #fff;
    font-size: 1.1rem;
    cursor: pointer;
    transition: transform 0.2s ease, opacity 0.2s ease, background 0.2s ease;
}

.spotlight-arrow:hover:not(:disabled) {
    transform: translateY(-2px);
    background: #1C315E;
}

.spotlight-arrow:disabled {
    opacity: 0.35;
    cursor: not-allowed;
}

.card-rank {
    position: absolute;
    top: 18px;
    right: 18px;
    z-index: 2;
    min-width: 48px;
    height: 48px;
    padding: 0 12px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    font-weight: 800;
    color: #fff;
    background: rgba(12, 27, 52, 0.85);
    box-shadow: 0 10px 24px rgba(12, 27, 52, 0.26);
}

.chef-strip {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border-radius: 18px;
    background: #F5F8FC;
    margin: 6px 0 2px;
}

.chef-avatar-home {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid rgba(255, 215, 0, 0.35);
}

.chef-strip-copy {
    display: grid;
    gap: 2px;
}

.chef-strip-copy strong {
    color: #1A2A44;
    font-size: 0.98rem;
}

.chef-strip-copy span {
    color: #667792;
    font-size: 0.88rem;
}

.card-highlights {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 4px;
}

.card-highlights span {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 999px;
    background: #EEF3F9;
    color: #43546C;
    font-size: 0.88rem;
    font-weight: 600;
}

.footer-rich {
    position: relative;
    background:
        radial-gradient(circle at top left, rgba(255, 215, 0, 0.12), transparent 32%),
        linear-gradient(135deg, #081A34, #13315C 58%, #214E7A);
    color: #fff;
    padding: 86px 0 34px;
    overflow: hidden;
}

.footer-rich-container {
    position: relative;
    z-index: 1;
}

.footer-grid {
    display: grid;
    grid-template-columns: 1.45fr repeat(3, minmax(180px, 1fr));
    gap: 30px;
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

.footer-description {
    color: rgba(255, 255, 255, 0.8);
    max-width: 520px;
    margin: 18px 0 20px;
}

.footer-food-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.footer-food-tags span {
    padding: 9px 14px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.12);
    font-size: 0.9rem;
}

.footer-column h3 {
    font-size: 1.1rem;
    margin-bottom: 18px;
}

.footer-column ul {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    gap: 12px;
}

.footer-column a,
.footer-contact-list li {
    color: rgba(255, 255, 255, 0.82);
    text-decoration: none;
    transition: color 0.2s ease;
}

.footer-column a:hover {
    color: #FFD700;
}

.footer-contact-list li {
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

.footer-contact-list i {
    color: #FFD700;
    margin-top: 3px;
}

.footer-social-icons {
    margin-top: 18px;
    display: flex;
    gap: 12px;
}

.footer-social-icons a {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    text-decoration: none;
}

.footer-social-icons a:hover {
    background: #FFD700;
    color: #17304F;
}

.footer-animation-strip {
    position: relative;
    margin: 36px 0 26px;
    height: 112px;
    border-radius: 26px;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.12);
    overflow: hidden;
}

.footer-road {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 26px;
    height: 6px;
    background: repeating-linear-gradient(90deg, rgba(255, 255, 255, 0.65) 0 36px, transparent 36px 62px);
}

.delivery-scooter {
    position: absolute;
    left: -220px;
    bottom: 18px;
    display: inline-flex;
    align-items: center;
    gap: 12px;
    padding: 10px 18px;
    border-radius: 999px;
    background: rgba(255, 215, 0, 0.16);
    color: #fff4c7;
    animation: rideAcross 12s linear infinite;
}

.delivery-scooter i {
    font-size: 1.4rem;
    color: #FFD700;
}

.footer-food-marquee {
    position: absolute;
    top: 18px;
    left: 0;
    display: flex;
    gap: 16px;
    white-space: nowrap;
    animation: floatFoods 15s linear infinite;
}

.footer-food-marquee span {
    display: inline-flex;
    align-items: center;
    padding: 10px 14px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.12);
    color: #fff;
}

.footer-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 18px;
    flex-wrap: wrap;
    color: rgba(255, 255, 255, 0.76);
}

.footer-mini-links {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
}

.footer-mini-links a {
    color: rgba(255, 255, 255, 0.76);
    text-decoration: none;
}

.footer-mini-links a:hover {
    color: #FFD700;
}

@keyframes rideAcross {
    0% { transform: translateX(0); }
    100% { transform: translateX(calc(100vw + 320px)); }
}

@keyframes floatFoods {
    0% { transform: translateX(100%); }
    100% { transform: translateX(-110%); }
}

.time i, .price i {
    color: hsl(var(--card-hue), 80%, 50%);
    font-size: 1.2rem;
}

.time span, .price span {
    font-weight: 600;
    color: #1A2A44;
}

/* Testimonial Section */
.testimonial-section {
    background-color: #1C315E;
    color: #FFFFFF;
    padding: 80px 0;
}

.testimonial-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 28px;
}

.testimonial-card {
    background-color: #2A447A;
    padding: 28px;
    border-radius: 16px;
    transition: transform 0.3s;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.testimonial-card:hover {
    transform: translateY(-6px);
}

.quote-title {
    font-size: 1.4rem;
    font-weight: 700;
    margin-bottom: 14px;
    color: #FFD700;
}

.testimonial-text {
    font-size: 1.05rem;
    opacity: 0.92;
    margin-bottom: 24px;
    color: #E6ECF4;
    flex-grow: 1;
}

.customer-info {
    display: flex;
    align-items: center;
    gap: 14px;
}

.customer-image {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #FFD700;
}

.customer-name {
    font-size: 1.2rem;
    font-weight: 700;
    color: #FFFFFF;
}

.customer-location {
    font-size: 0.95rem;
    opacity: 0.85;
    color: #DDE4EE;
}

/* Enhanced SlideUp Animation with Bounce */
@keyframes slideUp {
    0% { transform: translateY(50px); opacity: 0; }
    70% { transform: translateY(-10px); opacity: 1; }
    85% { transform: translateY(5px); }
    100% { transform: translateY(0); opacity: 1; }
}

/* Responsive Design */
@media (max-width: 768px) {
    #top-selling-section {
        background:
            linear-gradient(135deg, rgba(255, 255, 255, 0.96), rgba(245, 249, 255, 0.96)),
            url('assets/images/hilsha.jfif') top left / 130px auto no-repeat,
            url('assets/images/morgi.jfif') top right / 140px auto no-repeat;
    }

    #top-selling-section .container::before,
    #top-selling-section .container::after {
        width: 110px;
        height: 110px;
        opacity: 0.12;
    }

    .header-container {
        grid-template-columns: 1fr;
        justify-items: stretch;
    }

    .search-form-home {
        grid-template-columns: 1fr;
    }

    .homepage-nav-pills,
    .user-actions {
        justify-content: center;
    }

    .homepage-nav-pills {
        gap: 8px;
    }

    .floating-food {
        opacity: 0.14;
    }

    .food-1,
    .food-2,
    .food-3,
    .food-4 {
        width: 92px;
        height: 92px;
    }

    .food-5,
    .food-6 {
        width: 74px;
        height: 74px;
    }

    .hero h1 {
        font-size: 2.5rem;
    }

    .hero p {
        font-size: 1.2rem;
    }

    .section-title, .st {
        font-size: 2rem;
    }

    .food-grid, .testimonial-grid {
        grid-template-columns: 1fr;
    }

    .hero .btn {
        margin: 12px 0;
        display: block;
    }

    .food-animation {
        width: 100px;
        height: 100px;
        top: 10px;
        right: 10px;
    }

    .card {
        min-height: auto;
    }

    .section-intro,
    .footer-bottom {
        flex-direction: column;
        align-items: flex-start;
    }

    .spotlight-stats,
    .footer-grid {
        grid-template-columns: 1fr;
    }

    .section-copy,
    .section-subtitle {
        max-width: 100%;
    }

    .food-name {
        font-size: 1.6rem;
    }

    .chef-info, .location-info, .cuisine, .rating-value {
        font-size: 1rem;
    }

    .time, .price {
        font-size: 0.95rem;
    }
}

@media (max-width: 520px) {
    #top-selling-section .container::before,
    #top-selling-section .container::after {
        display: none;
    }

    .container {
        padding: 0 16px;
    }

    .homepage-nav-pills {
        justify-content: center;
    }

    .homepage-nav-pills a:nth-child(n+2) {
        display: none;
    }

    .search-form-home {
        gap: 8px;
    }

    .food-2,
    .food-4,
    .food-6 {
        display: none;
    }

    .hero {
        padding: 96px 0 82px;
    }
}
</style>

<div class="page-food-backdrop" aria-hidden="true">
    <div class="floating-food food-1" data-accent="#ff6b35" data-wash="rgba(255,107,53,0.14)" data-speed="0.12">
        <span>🍔</span>
    </div>
    <div class="floating-food food-2" data-accent="#ffb703" data-wash="rgba(255,183,3,0.14)" data-speed="0.16">
        <span>🍕</span>
    </div>
    <div class="floating-food food-3" data-accent="#2a9d8f" data-wash="rgba(42,157,143,0.13)" data-speed="0.1">
        <img src="assets/images/hilsha.jfif" alt="">
    </div>
    <div class="floating-food food-4" data-accent="#8ac926" data-wash="rgba(138,201,38,0.13)" data-speed="0.15">
        <img src="assets/images/morgi.jfif" alt="">
    </div>
    <div class="floating-food food-5" data-accent="#4361ee" data-wash="rgba(67,97,238,0.12)" data-speed="0.08">
        <img src="assets/images/aloBorta.jfif" alt="">
    </div>
    <div class="floating-food food-6 emoji-food" data-accent="#f72585" data-wash="rgba(247,37,133,0.12)" data-speed="0.11">
        <span>🍛</span>
    </div>
</div>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <h1>Delightful Homemade Food Delivered with Ease</h1>
        <p>Enjoy authentic home-cooked meals prepared by talented chefs in your neighborhood</p>
        <a href="#top-selling-section" class="btn">Explore Food</a>
        <a href="customer/login.php" class="btn secondary">Customer Login</a>
        <a href="chef/login.php" class="btn secondary">Chef Login</a>
    </div>
    <!-- Food Animation -->
    <div class="food-animation">
        <img src="assets/images/morgi.jfif" alt="মুরগির মাংস">
        <img src="assets/images/aloBorta.jfif" alt="আলু ভর্তা">
        <img src="assets/images/hilsha.jfif" alt="ইলিশ মাছ">
    </div>
</section>

<?php
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchIn = isset($_GET['search_in']) ? trim((string) $_GET['search_in']) : 'all';
$allowedSearchScopes = ['all', 'dish', 'chef', 'location', 'category'];
if (!in_array($searchIn, $allowedSearchScopes, true)) {
    $searchIn = 'all';
}
$scopeLabelMap = [
    'all' => 'Everything',
    'dish' => 'Dishes',
    'chef' => 'Chefs',
    'location' => 'Locations',
    'category' => 'Categories',
];
$sectionTitle = $searchQuery !== ''
    ? "Discovering " . ($scopeLabelMap[$searchIn] ?? 'Everything') . " for '" . htmlspecialchars($searchQuery) . "'"
    : "Top Selling Items";

function resolveHomepageFoodImage(array $item): string
{
    $fallbackImages = [
        'veg' => 'assets/images/aloBorta.jfif',
        'vegetarian' => 'assets/images/aloBorta.jfif',
        'nonveg' => 'assets/images/morgi.jfif',
        'non-veg' => 'assets/images/morgi.jfif',
        'beef' => 'assets/images/goru.jfif',
        'fish' => 'assets/images/hilsha.jfif',
        'rice' => 'assets/images/bhat.jfif',
    ];

    $imageName = trim((string) ($item['image'] ?? ''));
    if ($imageName !== '' && file_exists(__DIR__ . '/uploads/dishes/' . $imageName)) {
        return 'uploads/dishes/' . str_replace('%2F', '/', rawurlencode($imageName));
    }

    $categoryKey = strtolower(trim((string) ($item['category'] ?? '')));
    return $fallbackImages[$categoryKey] ?? 'assets/images/dish1.png';
}

require_once 'includes/db.php';

$heroStats = $db->selectOne(
    "SELECT
        (SELECT COUNT(*) FROM food_items WHERE is_available = 1 AND quantity > 0) AS available_items,
        (SELECT COUNT(DISTINCT chef_id) FROM food_items WHERE is_available = 1 AND quantity > 0) AS active_chefs,
        (SELECT COALESCE(SUM(oi.quantity), 0)
         FROM order_items oi
         JOIN orders o ON oi.order_id = o.id
         WHERE o.status = 'confirmed') AS meals_served"
);
?>
<section id="top-selling-section" class="section">
    <div class="container">
        <div class="section-intro">
            <div class="section-copy">
                <span class="section-kicker">Neighborhood Favorites</span>
                <h2 class="st"><?php echo $sectionTitle; ?></h2>
                <p class="section-subtitle">Handpicked Bengali comfort food and chef specials, ranked by what customers actually order and love most.</p>
            </div>
            <div class="spotlight-stats">
                <div class="spotlight-stat">
                    <strong><?php echo (int) ($heroStats['available_items'] ?? 0); ?></strong>
                    <span>Ready-to-order dishes</span>
                </div>
                <div class="spotlight-stat">
                    <strong><?php echo (int) ($heroStats['active_chefs'] ?? 0); ?></strong>
                    <span>Active home chefs</span>
                </div>
                <div class="spotlight-stat">
                    <strong><?php echo (int) ($heroStats['meals_served'] ?? 0); ?></strong>
                    <span>Meals already delivered</span>
                </div>
            </div>
        </div>
        <div class="food-grid spotlight-grid" id="spotlightGrid">
            <?php

            if ($searchQuery !== '') {
                // Fetch search results
                $query = "SELECT fi.id, fi.name, fi.price, fi.preparation_time, fi.category, fi.image,
                                 u.name AS chef_name, COALESCE(u.address, 'Not specified') AS chef_location,
                                 u.profile_image AS chef_profile_image,
                                 COALESCE(SUM(CASE WHEN o.status = 'confirmed' THEN oi.quantity ELSE 0 END), 0) AS total_sold,
                                 COALESCE(AVG(f.rating), 0) AS avg_rating
                          FROM food_items fi
                          JOIN users u ON fi.chef_id = u.id
                          LEFT JOIN order_items oi ON fi.id = oi.food_item_id
                          LEFT JOIN orders o ON oi.order_id = o.id
                          LEFT JOIN feedback f ON o.id = f.order_id
                          WHERE fi.is_available = 1 AND fi.quantity > 0";
                $params = [];

                switch ($searchIn) {
                    case 'dish':
                        $query .= " AND LOWER(fi.name) LIKE LOWER(?)";
                        $params[] = "%$searchQuery%";
                        break;
                    case 'chef':
                        $query .= " AND LOWER(u.name) LIKE LOWER(?)";
                        $params[] = "%$searchQuery%";
                        break;
                    case 'location':
                        $query .= " AND LOWER(u.address) LIKE LOWER(?)";
                        $params[] = "%$searchQuery%";
                        break;
                    case 'category':
                        $query .= " AND LOWER(fi.category) LIKE LOWER(?)";
                        $params[] = "%$searchQuery%";
                        break;
                    default:
                        $query .= " AND (
                            LOWER(fi.name) LIKE LOWER(?)
                            OR LOWER(u.name) LIKE LOWER(?)
                            OR LOWER(u.address) LIKE LOWER(?)
                            OR LOWER(fi.category) LIKE LOWER(?)
                        )";
                        $params = ["%$searchQuery%", "%$searchQuery%", "%$searchQuery%", "%$searchQuery%"];
                        break;
                }

                $query .= " GROUP BY fi.id, fi.name, fi.price, fi.preparation_time, fi.category, fi.image,
                                    u.name, u.address, u.profile_image
                            ORDER BY avg_rating DESC, total_sold DESC, fi.created_at DESC
                            LIMIT 12";
                $topSellingItems = $db->select($query, $params);
            } else {
                // Fetch top-selling items with average ratings
                $query = "SELECT fi.id, fi.name, fi.price, fi.preparation_time, fi.category, fi.image,
                                 u.name AS chef_name, COALESCE(u.address, 'Not specified') AS chef_location,
                                 u.profile_image AS chef_profile_image,
                                 COALESCE(SUM(oi.quantity), 0) AS total_sold,
                                 COALESCE(AVG(f.rating), 0) AS avg_rating
                          FROM order_items oi
                          JOIN orders o ON oi.order_id = o.id
                          JOIN food_items fi ON oi.food_item_id = fi.id
                          JOIN users u ON fi.chef_id = u.id
                          LEFT JOIN feedback f ON o.id = f.order_id
                          WHERE o.status = 'confirmed'
                          GROUP BY fi.id, fi.name, fi.price, fi.preparation_time, fi.category, fi.image,
                                    u.name, u.address, u.profile_image
                          ORDER BY total_sold DESC, avg_rating DESC
                          LIMIT 12";
                $topSellingItems = $db->select($query);
            }

            if ($topSellingItems === false || empty($topSellingItems)) {
                if ($searchQuery !== '') {
                    echo '<p style="text-align: center; grid-column: 1 / -1; color: #5A6A88; font-size: 1.2rem; font-weight: 500;">No food items found matching your search.</p>';
                } else {
                    echo '<p style="text-align: center; grid-column: 1 / -1; color: #5A6A88; font-size: 1.2rem; font-weight: 500;">No top selling items available yet.</p>';
                }
            } else {
                foreach ($topSellingItems as $index => $item) {
                    $imagePath = resolveHomepageFoodImage($item);
                    $badgeText = htmlspecialchars(ucwords(str_replace(['-', '_'], ' ', $item['category'] ?: 'Chef Special')));
                    $chefAvatar = getProfileImageUrl($item['chef_profile_image'] ?? '', '.', 'assets/images/default-profile.jpg');
                    echo '<div class="card" style="animation-delay: ' . ($index * 0.1) . 's;">';
                    echo '<div class="card-media">';
                    echo '<img class="card-image" src="' . htmlspecialchars($imagePath) . '" alt="' . htmlspecialchars($item['name']) . '">';
                    echo '<span class="card-rank">#' . ($index + 1) . '</span>';
                    echo '<span class="card-badge">' . $badgeText . '</span>';
                    echo '</div>';
                    echo '<div class="card-content">';
                    echo '<h2 class="food-name">' . htmlspecialchars($item['name']) . '</h2>';
                    echo '<div class="chef-strip">';
                    echo '<img class="chef-avatar-home" src="' . htmlspecialchars($chefAvatar) . '" alt="' . htmlspecialchars($item['chef_name']) . '">';
                    echo '<div class="chef-strip-copy">';
                    echo '<strong>' . htmlspecialchars($item['chef_name']) . '</strong>';
                    echo '<span>Kitchen: ' . htmlspecialchars($item['chef_location']) . '</span>';
                    echo '</div>';
                    echo '</div>';
                    echo '<div class="cuisine-rating">';
                    echo '<p class="cuisine">' . htmlspecialchars($item['category'] ?: 'Bangla Food') . '</p>';
                    echo '<div class="rating">';
                    echo '<span class="star">★</span>';
                    echo '<span class="rating-value">' . number_format($item['avg_rating'], 1) . '</span>';
                    echo '</div>';
                    echo '</div>';
                    echo '<div class="card-highlights">';
                    echo '<span><i class="fas fa-fire"></i> ' . (int) $item['total_sold'] . ' sold</span>';
                    echo '<span><i class="fas fa-bowl-food"></i> Homemade favorite</span>';
                    echo '</div>';
                    echo '<div class="card-footer">';
                    echo '<div class="time">';
                    echo '<i class="fa-regular fa-clock"></i>';
                    echo '<span>' . htmlspecialchars($item['preparation_time']) . ' Mins</span>';
                    echo '</div>';
                    echo '<div class="price">';
                    echo '<i class="fa-solid fa-tag"></i>';
                    echo '<span>৳' . number_format($item['price'], 2) . '</span>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
            }
            ?>
        </div>
        <div class="spotlight-pagination" id="spotlightPagination">
            <button type="button" class="spotlight-arrow" id="spotlightPrev" aria-label="Previous items">&#8592;</button>
            <span class="spotlight-page-indicator" id="spotlightPageIndicator">Page 1</span>
            <button type="button" class="spotlight-arrow" id="spotlightNext" aria-label="Next items">&#8594;</button>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section id="testimonial-section" class="testimonial-section">
    <div class="container">
        <h2 class="section-title" style="color: white;">What Our Customers Say</h2>
        <div class="testimonial-grid">
            <?php
            // Fetch feedback for testimonials
            $feedbackQuery = "SELECT f.id, f.rating, f.comment, f.created_at, 
                                     u.name AS customer_name, u.profile_image, o.delivery_address
                              FROM feedback f
                              JOIN users u ON f.customer_id = u.id
                              JOIN orders o ON f.order_id = o.id
                              ORDER BY f.created_at DESC
                              LIMIT 3";
            $feedbacks = $db->select($feedbackQuery);

            if ($feedbacks === false || empty($feedbacks)) {
                echo '<p style="text-align: center; color: #E6ECF4; font-size: 1.2rem; font-weight: 500;">No feedback available yet.</p>';
            } else {
                foreach ($feedbacks as $index => $feedback) {
                    echo '<div class="testimonial-card" style="animation-delay: ' . ($index * 0.1) . 's;">';
                    echo '<h3 class="quote-title">Rating: ' . $feedback['rating'] . '/5</h3>';
                    echo '<p class="testimonial-text">' . htmlspecialchars($feedback['comment'] ?: 'No comment provided') . '</p>';
                    echo '<div class="customer-info">';
                    echo '<img src="' . htmlspecialchars(getProfileImageUrl($feedback['profile_image'] ?? '', '.', 'assets/images/default-profile.jpg')) . '" alt="' . htmlspecialchars($feedback['customer_name']) . '" class="customer-image">';
                    echo '<div class="customer-details">';
                    echo '<h4 class="customer-name">' . htmlspecialchars($feedback['customer_name']) . '</h4>';
                    echo '<p class="customer-location">' . htmlspecialchars($feedback['delivery_address'] ?: 'Not specified') . '</p>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
            }
            ?>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const grid = document.getElementById('spotlightGrid');
    const prev = document.getElementById('spotlightPrev');
    const next = document.getElementById('spotlightNext');
    const indicator = document.getElementById('spotlightPageIndicator');
    const floatingFoods = Array.from(document.querySelectorAll('.floating-food'));
    let activeFoodIndex = -1;

    if (!grid || !prev || !next || !indicator) {
        return;
    }

    const cards = Array.from(grid.querySelectorAll('.card'));
    const pageSize = 6;
    const totalPages = Math.max(1, Math.ceil(cards.length / pageSize));
    let currentPage = 1;

    function renderSpotlightPage(page) {
        currentPage = Math.min(Math.max(page, 1), totalPages);
        const start = (currentPage - 1) * pageSize;
        const end = start + pageSize;

        cards.forEach(function (card, index) {
            card.style.display = index >= start && index < end ? '' : 'none';
        });

        indicator.textContent = 'Page ' + currentPage + ' of ' + totalPages;
        prev.disabled = currentPage === 1;
        next.disabled = currentPage === totalPages;
    }

    if (cards.length <= pageSize) {
        indicator.textContent = 'Top 6 spotlight';
        prev.disabled = true;
        next.disabled = true;
        return;
    }

    prev.addEventListener('click', function () {
        renderSpotlightPage(currentPage - 1);
    });

    next.addEventListener('click', function () {
        renderSpotlightPage(currentPage + 1);
    });

    renderSpotlightPage(1);

    function updateFoodBackdrop() {
        const scrollY = window.scrollY || window.pageYOffset;

        floatingFoods.forEach(function (food, index) {
            const speed = parseFloat(food.dataset.speed || '0.1');
            const direction = index % 2 === 0 ? 1 : -1;
            const xOffset = scrollY * speed * direction * 0.12;
            const yOffset = scrollY * speed;
            food.style.transform = 'translate3d(' + xOffset.toFixed(1) + 'px, ' + yOffset.toFixed(1) + 'px, 0)';
        });

        if (!floatingFoods.length) {
            return;
        }

        const nextIndex = Math.floor(scrollY / 260) % floatingFoods.length;
        if (nextIndex !== activeFoodIndex) {
            activeFoodIndex = nextIndex;
            floatingFoods.forEach(function (food, index) {
                food.classList.toggle('active', index === activeFoodIndex);
            });

            const activeFood = floatingFoods[activeFoodIndex];
            document.body.style.setProperty('--page-accent', activeFood.dataset.accent || '#ffb703');
            document.body.style.setProperty('--page-wash', activeFood.dataset.wash || 'rgba(255, 183, 3, 0.12)');
        }
    }

    updateFoodBackdrop();
    window.addEventListener('scroll', updateFoodBackdrop, { passive: true });
});
</script>

<?php
require_once 'includes/footer.php';
?>
