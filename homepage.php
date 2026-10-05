<?php
/**
 * YatraPath — Intelligent Travel Itinerary Planner
 * Slogan: "Let the path find you"
 * Living Landscape & Algorithmic Travel Portal
 * Developed for Nepal Tourism · 6th Semester BCA Capstone Project, Tribhuvan University
 */
declare(strict_types=1);

require_once __DIR__ . '/config/session.php';

$userLoggedIn = isLoggedIn();
$userName     = getCurrentUserName() ?? '';
$userRole     = getCurrentUserRole() ?? '';
$userIsAdmin  = isAdmin();

// Database Stats & Featured Content
$stats = [
    'dest'    => 7,
    'act'     => 21,
    'plans'   => 45,
    'reviews' => 12
];
$dbDestinations = [];
$dbReviews      = [];

if (file_exists(__DIR__ . '/config/database.php')) {
    try {
        require_once __DIR__ . '/config/database.php';
        $db = Database::getInstance()->getConnection();

        // 1. Live Counters
        $destCount = (int)$db->query("SELECT COUNT(*) FROM destinations")->fetchColumn();
        if ($destCount > 0) $stats['dest'] = $destCount;

        $actCount = (int)$db->query("SELECT COUNT(*) FROM activities")->fetchColumn();
        if ($actCount > 0) $stats['act'] = $actCount;

        $itinCount = (int)$db->query("SELECT COUNT(*) FROM itineraries")->fetchColumn();
        if ($itinCount > 0) $stats['plans'] = $itinCount;

        $revCount = (int)$db->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
        if ($revCount > 0) $stats['reviews'] = $revCount;

        // 2. Featured Destinations
        $destStmt = $db->query("
            SELECT id, name, region, avg_cost_per_day, avg_rating, suitable_seasons, image_url
            FROM destinations 
            ORDER BY avg_rating DESC, id ASC 
            LIMIT 6
        ");
        $dbDestinations = $destStmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Recent Real Reviews
        $revStmt = $db->query("
            SELECT r.id, r.rating, r.comment, r.trip_month_year, u.name AS user_name, d.name AS dest_name
            FROM reviews r
            LEFT JOIN users u ON r.user_id = u.id
            LEFT JOIN destinations d ON r.destination_id = d.id
            ORDER BY r.id DESC 
            LIMIT 6
        ");
        $dbReviews = $revStmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Throwable $e) {
        // Graceful fallback to default values
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>YatraPath — Let the path find you</title>
<meta name="description" content="YatraPath builds a personal morning-to-evening itinerary across Nepal in under three seconds. Season-aware. Budget-smart. Route-optimised.">
<link rel="icon" type="image/svg+xml" href="logo.svg">

<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400;1,500&family=Inter:wght@300;400;500;600;700&family=Noto+Serif+Devanagari:wght@300;400;600&display=swap" rel="stylesheet">

<style>
/* ═══════════════════════════════════════════════════
   TOKENS
═══════════════════════════════════════════════════ */
:root {
  --ink:        #0F1A22;
  --pine:       #1E3A34;
  --slate:      #2B3A45;
  --snow:       #F4EFE6;
  --paper:      #EAE3D6;
  --marigold:   #C8973A;
  --terracotta: #B4533A;
  --sage:       #8FA58A;
  --stone:      #A39B8D;
  --stone-lt:   #C8C0B4;

  --ease-out:    cubic-bezier(0.22, 1, 0.36, 1);
  --ease-in-out: cubic-bezier(0.76, 0, 0.24, 1);
  --ease-soft:   cubic-bezier(0.33, 1, 0.68, 1);

  --dur-micro: 160ms;
  --dur-ui:    380ms;
  --dur-reveal:800ms;

  --font-display: 'Cormorant Garamond', Georgia, serif;
  --font-body:    'Inter', system-ui, sans-serif;
  --font-deva:    'Noto Serif Devanagari', serif;

  --nav-h: 68px;
  --section-pad: clamp(5rem, 10vw, 9rem);
  --gap: clamp(1.5rem, 3vw, 2.5rem);
}

/* ═══════════════════════════════════════════════════
   RESET & BASE
═══════════════════════════════════════════════════ */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; font-size: 16px; }
body {
  font-family: var(--font-body);
  background: var(--snow);
  color: var(--ink);
  overflow-x: hidden;
  -webkit-font-smoothing: antialiased;
}
body.intro-active { overflow: hidden; }
img { display: block; max-width: 100%; }
a { color: inherit; text-decoration: none; }
button { border: none; background: none; cursor: pointer; font: inherit; }

/* Paper grain */
body::after {
  content: '';
  position: fixed; inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='200' height='200' filter='url(%23n)' opacity='0.035'/%3E%3C/svg%3E");
  pointer-events: none;
  z-index: 9999;
  opacity: 0.04;
}

/* Skip link */
.skip-link {
  position: fixed; top: -100%; left: 1rem;
  background: var(--marigold); color: var(--ink);
  padding: 0.5rem 1rem; border-radius: 4px;
  font-size: 0.875rem; font-weight: 500; z-index: 99999;
  transition: top 0.2s;
}
.skip-link:focus { top: 1rem; }

/* Scroll progress */
#scroll-progress {
  position: fixed; top: 0; left: 0;
  width: 100%; height: 2px;
  background: var(--marigold);
  transform-origin: left;
  transform: scaleX(0);
  z-index: 9998;
  transition: transform 0.05s linear;
}

/* ═══════════════════════════════════════════════════
   INTRO OVERLAY
═══════════════════════════════════════════════════ */
#intro {
  position: fixed; inset: 0;
  background: var(--ink);
  z-index: 9000;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
}
#intro.done { pointer-events: none; }

#intro-logo-wrap {
  will-change: transform, opacity, filter;
  transform-origin: center center;
  display: flex; flex-direction: column; align-items: center; gap: 1rem;
}
#intro-logo-wrap img {
  width: 140px; height: auto;
  display: block;
}

#intro-skip {
  position: absolute; bottom: 2rem; right: 2rem;
  color: rgba(244,239,230,0.35);
  font-size: 0.75rem; letter-spacing: 0.12em;
  font-family: var(--font-body);
}

/* ═══════════════════════════════════════════════════
   NAVBAR
═══════════════════════════════════════════════════ */
#navbar {
  position: fixed; top: 0; left: 0; right: 0;
  height: var(--nav-h);
  z-index: 800;
  transition: background var(--dur-ui) var(--ease-soft),
              box-shadow var(--dur-ui) var(--ease-soft),
              transform var(--dur-ui) var(--ease-soft);
}
#navbar.scrolled {
  background: rgba(244,239,230,0.94);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  box-shadow: 0 1px 0 rgba(15,26,34,0.08);
}
#navbar.hidden { transform: translateY(-100%); }

.nav-inner {
  max-width: 1280px; margin: 0 auto;
  height: 100%;
  display: flex; align-items: center;
  padding: 0 clamp(1.25rem, 4vw, 2.5rem);
  gap: 2rem;
}

#nav-logo {
  flex-shrink: 0;
  visibility: hidden; /* shown after intro handoff */
  display: flex; align-items: center; gap: 0.5rem;
}
#nav-logo img { width: 120px; height: auto; }

.nav-links {
  display: flex; gap: 0.25rem;
  margin-left: auto;
}
.nav-links a {
  font-size: 0.875rem; font-weight: 500;
  color: var(--snow); padding: 0.5rem 0.75rem;
  position: relative; letter-spacing: 0.01em;
  transition: color var(--dur-micro) var(--ease-out);
}
#navbar.scrolled .nav-links a { color: var(--slate); }
.nav-links a::after {
  content: ''; position: absolute;
  bottom: 4px; left: 0.75rem; right: 0.75rem;
  height: 1px; background: var(--marigold);
  transform: scaleX(0); transform-origin: left;
  transition: transform var(--dur-ui) var(--ease-out);
}
.nav-links a:hover::after { transform: scaleX(1); }
.nav-links a:hover { color: var(--snow); }
#navbar.scrolled .nav-links a:hover { color: var(--ink); }

.nav-actions {
  display: flex; align-items: center; gap: 0.75rem;
  margin-left: 1.5rem;
}
.nav-login {
  font-size: 0.875rem; font-weight: 500;
  color: rgba(244,239,230,0.85);
  padding: 0.5rem 0.75rem;
  transition: color var(--dur-micro);
}
#navbar.scrolled .nav-login { color: var(--stone); }
.nav-login:hover { color: var(--snow) !important; }
#navbar.scrolled .nav-login:hover { color: var(--ink) !important; }

.btn-primary {
  display: inline-flex; align-items: center; gap: 0.4rem;
  background: var(--marigold); color: var(--ink);
  font-size: 0.875rem; font-weight: 600;
  padding: 0.55rem 1.25rem; border-radius: 100px;
  position: relative; overflow: hidden;
  transition: transform var(--dur-micro), box-shadow var(--dur-micro);
}
.btn-primary::before {
  content: ''; position: absolute;
  inset: 0; background: rgba(255,255,255,0.18);
  transform: translateX(-101%);
  transition: transform 350ms var(--ease-soft);
}
.btn-primary:hover::before { transform: translateX(0); }
.btn-primary:hover { box-shadow: 0 4px 16px rgba(200,151,58,0.35); }
.btn-primary:active { transform: scale(0.98); }

.btn-outline {
  display: inline-flex; align-items: center; gap: 0.4rem;
  border: 1px solid rgba(244,239,230,0.4);
  color: var(--snow);
  font-size: 0.875rem; font-weight: 500;
  padding: 0.55rem 1.25rem; border-radius: 100px;
  transition: border-color var(--dur-micro), color var(--dur-micro), background var(--dur-micro);
}
.btn-outline:hover {
  border-color: var(--snow);
  background: rgba(244,239,230,0.08);
}

#hamburger {
  display: none; flex-direction: column; gap: 5px;
  padding: 0.5rem; cursor: pointer; margin-left: auto;
}
#hamburger span {
  width: 22px; height: 1.5px;
  background: var(--snow);
  transition: transform 0.3s, opacity 0.3s, background 0.3s;
}
#navbar.scrolled #hamburger span { background: var(--ink); }

/* Mobile menu */
#mobile-menu {
  position: fixed; inset: 0;
  background: var(--ink);
  z-index: 850; display: flex;
  flex-direction: column; align-items: center; justify-content: center;
  gap: 1.8rem;
  opacity: 0; pointer-events: none;
  transition: opacity 0.35s var(--ease-soft);
}
#mobile-menu.open { opacity: 1; pointer-events: all; }
#mobile-menu a {
  font-family: var(--font-display);
  font-size: clamp(1.8rem, 7vw, 2.5rem);
  color: var(--snow); font-weight: 300;
  opacity: 0; transform: translateY(20px);
  transition: opacity 0.4s, transform 0.4s;
}
#mobile-menu.open a { opacity: 1; transform: translateY(0); }
#menu-close {
  position: absolute; top: 1.5rem; right: 1.5rem;
  color: var(--stone); font-size: 1.5rem; cursor: pointer;
}

/* ═══════════════════════════════════════════════════
   HERO
═══════════════════════════════════════════════════ */
#hero {
  position: relative;
  height: 100dvh; min-height: 620px;
  display: flex; flex-direction: column;
  align-items: flex-start; justify-content: center;
  overflow: hidden;
}

/* Slideshow */
.hero-slides { position: absolute; inset: 0; }
.hero-slide {
  position: absolute; inset: 0;
  background-size: cover; background-position: center;
  opacity: 0;
  transition: opacity 1.2s var(--ease-soft);
  transform-origin: center;
}
.hero-slide.active {
  opacity: 1;
  animation: kenburns 22s linear forwards;
}
.hero-slide.exiting { opacity: 0; }

@keyframes kenburns {
  from { transform: scale(1); }
  to   { transform: scale(1.07); }
}

/* Gradient overlays */
.hero-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(
    to right,
    rgba(15,26,34,0.78) 0%,
    rgba(15,26,34,0.48) 55%,
    rgba(15,26,34,0.2) 100%
  );
  pointer-events: none;
}
.hero-overlay-bottom {
  position: absolute; bottom: 0; left: 0; right: 0;
  height: 35%;
  background: linear-gradient(to top, rgba(15,26,34,0.6), transparent);
  pointer-events: none;
}

/* Mist animation */
.mist {
  position: absolute; inset: 0; pointer-events: none; opacity: 0.18; overflow: hidden;
}
.mist-band {
  position: absolute; width: 200%; height: 100%;
  background: radial-gradient(ellipse at 50% 50%, rgba(244,239,230,0.5), transparent 70%);
  animation: drift 28s infinite linear;
}
.mist-band:nth-child(2) {
  top: 20%; animation: drift 36s infinite linear reverse;
}
@keyframes drift {
  from { transform: translateX(0); }
  to   { transform: translateX(-50%); }
}

/* Prayer flags SVG */
.flags-wrap {
  position: absolute; top: 85px; right: 5%;
  pointer-events: none; opacity: 0.75;
  filter: drop-shadow(0 4px 12px rgba(0,0,0,0.3));
}

/* Season widget */
.season-widget {
  position: absolute; top: 90px; left: clamp(1.25rem, 5vw, 4rem);
  background: rgba(15,26,34,0.55);
  border: 1px solid rgba(244,239,230,0.15);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border-radius: 100px;
  padding: 0.4rem 1rem;
  z-index: 10;
}
.season-widget-inner {
  display: flex; align-items: center; gap: 0.75rem;
  font-size: 0.75rem; color: var(--snow);
}
.season-time { font-family: monospace; opacity: 0.7; font-size: 0.8rem; }
.season-name { font-weight: 600; color: var(--marigold); }
.season-tip  { opacity: 0.8; border-left: 1px solid rgba(244,239,230,0.2); padding-left: 0.75rem; }

/* Hero content */
.hero-content {
  position: relative; z-index: 10;
  max-width: 1280px; width: 100%;
  margin: 0 auto;
  padding: 0 clamp(1.25rem, 5vw, 4rem);
}
.hero-eyebrow {
  font-family: var(--font-deva);
  font-size: clamp(0.85rem, 1.5vw, 1.05rem);
  color: var(--marigold);
  letter-spacing: 0.08em;
  margin-bottom: 0.75rem;
  opacity: 0; transform: translateY(15px);
  transition: opacity 0.6s var(--ease-out), transform 0.6s var(--ease-out);
}
.hero-eyebrow.visible { opacity: 1; transform: none; }

.hero-h1 {
  font-family: var(--font-display);
  font-size: clamp(3rem, 7.5vw, 5.8rem);
  font-weight: 300; line-height: 1.02;
  color: var(--snow); margin-bottom: 1.25rem;
}
.hero-h1 em { font-style: italic; font-weight: 400; color: var(--marigold); }
.hero-h1-inner {
  display: block;
  opacity: 0; transform: translateY(25px);
  transition: opacity 0.8s var(--ease-out), transform 0.8s var(--ease-out);
}
.hero-h1-inner.visible { opacity: 1; transform: none; }

/* Ticker */
.hero-ticker {
  display: flex; align-items: center; gap: 0.75rem;
  margin-bottom: 1.5rem;
  font-size: clamp(0.95rem, 2vw, 1.15rem);
  color: var(--snow);
  opacity: 0; transform: translateY(15px);
  transition: opacity 0.6s var(--ease-out), transform 0.6s var(--ease-out);
}
.hero-ticker.visible { opacity: 1; transform: none; }
.ticker-label { opacity: 0.6; font-weight: 300; }
.ticker-slot {
  position: relative; height: 1.6em; overflow: hidden;
  min-width: 220px;
}
.ticker-word {
  position: absolute; top: 0; left: 0;
  font-family: var(--font-display); font-style: italic;
  font-size: 1.25em; font-weight: 400; color: var(--marigold);
  white-space: nowrap;
  transition: transform 0.6s var(--ease-soft), opacity 0.6s;
}
.ticker-word.next    { transform: translateY(100%); opacity: 0; }
.ticker-word.current { transform: translateY(0);    opacity: 1; }
.ticker-word.prev    { transform: translateY(-100%);opacity: 0; }

.hero-sub {
  max-width: 540px;
  font-size: clamp(0.95rem, 1.6vw, 1.1rem);
  line-height: 1.65; color: rgba(244,239,230,0.85);
  font-weight: 300; margin-bottom: 2.25rem;
  opacity: 0; transform: translateY(15px);
  transition: opacity 0.6s var(--ease-out), transform 0.6s var(--ease-out);
}
.hero-sub.visible { opacity: 1; transform: none; }

/* Quick search bar */
#quick-search {
  display: flex; align-items: stretch;
  background: rgba(244,239,230,0.96);
  border: 1px solid rgba(255,255,255,0.4);
  border-radius: 16px;
  box-shadow: 0 16px 36px rgba(15,26,34,0.35);
  max-width: 760px;
  overflow: hidden;
  opacity: 0; transform: translateY(20px);
  transition: opacity 0.6s var(--ease-out), transform 0.6s var(--ease-out);
}
#quick-search.visible { opacity: 1; transform: none; }

.qs-field {
  flex: 1; padding: 0.85rem 1.2rem;
  display: flex; flex-direction: column; justify-content: center;
  border-right: 1px solid rgba(15,26,34,0.08);
}
.qs-field:last-of-type { border-right: none; }
.qs-label {
  font-size: 0.7rem; font-weight: 600; text-transform: uppercase;
  letter-spacing: 0.08em; color: var(--stone); margin-bottom: 0.25rem;
}
.qs-input {
  border: none; background: transparent;
  font-family: var(--font-body); font-size: 0.95rem; font-weight: 500;
  color: var(--ink); outline: none; width: 100%;
}
.qs-input::placeholder { color: var(--stone-lt); }

.qs-stepper {
  display: flex; align-items: center; justify-content: space-between;
}
.qs-stepper button {
  width: 26px; height: 26px; border-radius: 50%;
  background: rgba(15,26,34,0.06); color: var(--ink);
  display: flex; align-items: center; justify-content: center;
  font-weight: 600; font-size: 1rem; transition: background 0.15s;
}
.qs-stepper button:hover { background: rgba(15,26,34,0.12); }
.qs-days-val { font-weight: 600; font-size: 1rem; color: var(--ink); }

.qs-meta {
  display: flex; align-items: center; gap: 0.5rem; margin-top: 0.2rem;
  font-size: 0.72rem;
}
.qs-per-day { color: var(--stone); font-weight: 500; }
.qs-tier {
  font-weight: 600; font-size: 0.68rem; padding: 0.1rem 0.45rem;
  border-radius: 4px;
}

.qs-cta {
  background: var(--ink); color: var(--snow);
  padding: 0 1.8rem; display: flex; align-items: center; gap: 0.5rem;
  font-weight: 600; font-size: 0.95rem; cursor: pointer;
  transition: background 0.2s, color 0.2s;
}
.qs-cta:hover { background: var(--pine); }

/* Hero caption */
.hero-caption {
  position: absolute; bottom: 30px; left: clamp(1.25rem, 5vw, 4rem);
  z-index: 10; display: flex; flex-direction: column; gap: 0.4rem;
}
.hero-caption-text {
  font-size: 0.75rem; color: rgba(244,239,230,0.6);
  letter-spacing: 0.05em; font-family: monospace;
}
.hero-progress {
  width: 140px; height: 2px;
  background: rgba(244,239,230,0.15);
  border-radius: 2px; overflow: hidden;
}
.hero-progress-bar {
  height: 100%; width: 100%; background: var(--marigold);
  transform-origin: left; transform: scaleX(0);
}

/* Scroll cue */
.scroll-cue {
  position: absolute; bottom: 25px; right: clamp(1.25rem, 5vw, 4rem);
  z-index: 10; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;
  color: rgba(244,239,230,0.5); font-size: 0.7rem; letter-spacing: 0.1em;
  text-transform: uppercase;
  opacity: 0; transition: opacity 0.6s;
}
.scroll-cue.visible { opacity: 1; }
.scroll-cue-line {
  width: 1px; height: 32px; background: rgba(244,239,230,0.3);
  animation: pulse-down 2s infinite ease-in-out;
}
@keyframes pulse-down {
  0%   { transform: scaleY(0); transform-origin: top; }
  50%  { transform: scaleY(1); transform-origin: top; }
  51%  { transform: scaleY(1); transform-origin: bottom; }
  100% { transform: scaleY(0); transform-origin: bottom; }
}

/* Ridgeline SVG curve */
.hero-curve {
  position: absolute; bottom: -1px; left: 0; right: 0;
  pointer-events: none; z-index: 5;
}
.hero-curve svg { display: block; width: 100%; height: 60px; }

/* Trust strip */
.trust-strip {
  position: absolute; bottom: 0; left: 0; right: 0;
  display: flex; justify-content: center; gap: clamp(1.5rem, 4vw, 3.5rem);
  padding: 0.75rem 1rem; z-index: 15;
}
.trust-item {
  display: flex; align-items: center; gap: 0.4rem;
  font-size: 0.75rem; font-weight: 500; color: var(--slate);
  letter-spacing: 0.04em;
}
.trust-dot {
  width: 5px; height: 5px; border-radius: 50%; background: var(--marigold);
}

/* ═══════════════════════════════════════════════════
   MARQUEE
═══════════════════════════════════════════════════ */
#marquee {
  background: var(--snow);
  border-top: 1px solid rgba(15,26,34,0.06);
  border-bottom: 1px solid rgba(15,26,34,0.06);
  overflow: hidden; padding: 1rem 0;
  white-space: nowrap; user-select: none;
}
.marquee-track {
  display: inline-flex;
  animation: scroll-marquee 36s linear infinite;
}
.marquee-item {
  font-family: var(--font-display);
  font-size: 1.35rem; font-weight: 400; color: var(--slate);
  padding: 0 1.5rem; display: inline-flex; align-items: center; gap: 1.5rem;
}
.marquee-glyph { color: var(--marigold); font-size: 0.85rem; }
@keyframes scroll-marquee {
  from { transform: translateX(0); }
  to   { transform: translateX(-50%); }
}

/* ═══════════════════════════════════════════════════
   SECTION SHARED STYLES
═══════════════════════════════════════════════════ */
section { position: relative; padding: var(--section-pad) 0; }
.section-eyebrow {
  font-size: 0.75rem; font-weight: 600; text-transform: uppercase;
  letter-spacing: 0.12em; color: var(--marigold); margin-bottom: 0.6rem;
  display: flex; align-items: center; gap: 0.5rem;
}
.section-eyebrow::before {
  content: ''; width: 14px; height: 1px; background: var(--marigold);
}
.section-h2 {
  font-family: var(--font-display);
  font-size: clamp(2.2rem, 5vw, 3.8rem);
  font-weight: 300; line-height: 1.1; color: var(--ink);
  margin-bottom: 1.5rem;
}
.section-h2 em { font-style: italic; font-weight: 400; color: var(--pine); }

.section-header {
  display: flex; justify-content: space-between; align-items: flex-end;
  margin-bottom: clamp(2.5rem, 5vw, 4rem);
}
.view-all {
  font-size: 0.875rem; font-weight: 600; color: var(--ink);
  display: inline-flex; align-items: center; gap: 0.4rem;
  border-bottom: 1px solid var(--marigold); padding-bottom: 2px;
  transition: color 0.2s;
}
.view-all:hover { color: var(--marigold); }

/* Reveal animation */
.reveal {
  opacity: 0; transform: translateY(24px);
  transition: opacity var(--dur-reveal) var(--ease-out), transform var(--dur-reveal) var(--ease-out);
}
.reveal.visible { opacity: 1; transform: none; }
.reveal-delay-1 { transition-delay: 80ms; }
.reveal-delay-2 { transition-delay: 160ms; }
.reveal-delay-3 { transition-delay: 240ms; }
.reveal-delay-4 { transition-delay: 320ms; }

/* ═══════════════════════════════════════════════════
   HOW IT WORKS
═══════════════════════════════════════════════════ */
#how {
  background: var(--snow);
  border-bottom: 1px solid rgba(15,26,34,0.06);
}
.how-inner {
  max-width: 1280px; margin: 0 auto;
  padding: 0 clamp(1.25rem, 5vw, 4rem);
  display: grid; grid-template-columns: 1.2fr 1fr;
  gap: clamp(2.5rem, 6vw, 6rem); align-items: center;
}
.how-steps {
  position: relative; margin-top: 2.5rem;
  display: flex; flex-direction: column; gap: 2.5rem;
}
.how-path-line {
  position: absolute; top: 1.5rem; bottom: 1.5rem; left: 1.25rem;
  width: 1px; background: rgba(15,26,34,0.12);
  border-left: 1px dashed var(--marigold);
}
.how-step {
  display: flex; gap: 1.75rem; position: relative; z-index: 2;
}
.how-step-num {
  width: 2.5rem; height: 2.5rem; border-radius: 50%;
  background: var(--snow); border: 1px solid var(--marigold);
  display: flex; align-items: center; justify-content: center;
  font-family: var(--font-display); font-size: 1.1rem; font-weight: 500;
  color: var(--pine); flex-shrink: 0;
}
.how-step-body h3 {
  font-size: 1.15rem; font-weight: 600; color: var(--ink); margin-bottom: 0.4rem;
}
.how-step-body p {
  font-size: 0.92rem; color: var(--stone); line-height: 1.65;
}

.how-panel {
  background: var(--paper); border-radius: 20px; padding: 2rem;
  box-shadow: 0 12px 32px rgba(15,26,34,0.06);
  border: 1px solid rgba(15,26,34,0.06);
}
.mock-card {
  background: #FFF; border-radius: 14px; padding: 1.25rem;
  box-shadow: 0 4px 16px rgba(15,26,34,0.04);
}
.mock-card-row {
  display: flex; justify-content: space-between; align-items: center;
  padding: 0.65rem 0; border-bottom: 1px dashed rgba(15,26,34,0.08);
  font-size: 0.85rem;
}
.mock-card-row:last-child { border-bottom: none; }
.mock-time {
  font-weight: 600; font-size: 0.72rem; text-transform: uppercase;
  color: var(--marigold); width: 75px;
}
.mock-act  { flex: 1; color: var(--ink); font-weight: 500; }
.mock-cost { color: var(--stone); font-size: 0.8rem; }

/* ═══════════════════════════════════════════════════
   ENGINE DEMO (INTERACTIVE ALGORITHM)
═══════════════════════════════════════════════════ */
#engine {
  background: #0F1A22; color: var(--snow);
  overflow: hidden;
}
.engine-bg-blur {
  position: absolute; top: 10%; right: 10%; width: 450px; height: 450px;
  border-radius: 50%; background: radial-gradient(circle, rgba(30,58,52,0.6), transparent 70%);
  filter: blur(80px); pointer-events: none;
}
.engine-inner {
  max-width: 1280px; margin: 0 auto;
  padding: 0 clamp(1.25rem, 5vw, 4rem);
  display: grid; grid-template-columns: 1.1fr 1fr;
  gap: clamp(2.5rem, 6vw, 5rem); align-items: flex-start;
}
#engine .section-h2 { color: var(--snow); }
#engine .section-h2 em { color: var(--marigold); }

.engine-control-group { margin-bottom: 2rem; }
.engine-label {
  font-size: 0.78rem; font-weight: 600; text-transform: uppercase;
  letter-spacing: 0.08em; color: var(--stone); margin-bottom: 0.75rem;
}

/* Season tabs */
.season-tabs {
  display: flex; gap: 0.5rem; flex-wrap: wrap;
}
.season-tab {
  padding: 0.45rem 1rem; border-radius: 100px;
  font-size: 0.85rem; font-weight: 500; color: var(--snow);
  background: rgba(244,239,230,0.08); border: 1px solid rgba(244,239,230,0.15);
  transition: all 0.2s;
}
.season-tab:hover { background: rgba(244,239,230,0.15); }
.season-tab.active {
  background: var(--marigold); color: var(--ink);
  border-color: var(--marigold); font-weight: 600;
}

/* Budget slider */
.budget-slider-wrap { margin-bottom: 0.6rem; }
#budget-slider {
  width: 100%; -webkit-appearance: none; appearance: none;
  height: 4px; border-radius: 2px; background: rgba(244,239,230,0.2);
  outline: none;
}
#budget-slider::-webkit-slider-thumb {
  -webkit-appearance: none; appearance: none;
  width: 18px; height: 18px; border-radius: 50%;
  background: var(--marigold); cursor: pointer;
  box-shadow: 0 0 10px rgba(200,151,58,0.5);
}
.budget-display {
  display: flex; justify-content: space-between; align-items: center;
}
.budget-val { font-size: 1.25rem; font-weight: 600; color: var(--snow); }
.budget-tier-chip {
  font-size: 0.75rem; font-weight: 600; padding: 0.2rem 0.65rem;
  border-radius: 100px; background: rgba(200,151,58,0.2); color: var(--marigold);
}

/* Interest chips */
.interest-chips {
  display: flex; gap: 0.5rem; flex-wrap: wrap;
}
.interest-chip {
  padding: 0.4rem 0.85rem; border-radius: 100px;
  font-size: 0.8rem; font-weight: 500; color: rgba(244,239,230,0.7);
  background: rgba(244,239,230,0.06); border: 1px solid rgba(244,239,230,0.12);
  transition: all 0.2s;
}
.interest-chip:hover { color: var(--snow); border-color: rgba(244,239,230,0.3); }
.interest-chip.selected {
  background: rgba(143,165,138,0.25); border-color: var(--sage);
  color: #B4CBB0; font-weight: 600;
}

/* Algo stepper dots */
.algo-stepper {
  margin-top: 2rem; display: flex; flex-direction: column; gap: 0.6rem;
  border-top: 1px solid rgba(244,239,230,0.1); padding-top: 1.5rem;
}
.algo-step {
  display: flex; align-items: center; gap: 0.6rem;
  font-size: 0.78rem; color: rgba(244,239,230,0.5);
}
.algo-step-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: rgba(244,239,230,0.25);
  transition: background 0.3s;
}
.algo-step.active .algo-step-dot { background: var(--marigold); box-shadow: 0 0 8px var(--marigold); }
.algo-step.active { color: var(--snow); font-weight: 500; }
.algo-status { font-size: 0.75rem; color: var(--sage); margin-top: 0.4rem; }

/* Day result card */
.day-card {
  background: rgba(43,58,69,0.45);
  border: 1px solid rgba(244,239,230,0.15);
  border-radius: 18px; padding: 1.75rem;
  backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
  box-shadow: 0 16px 40px rgba(0,0,0,0.3);
}
.day-card-header {
  display: flex; justify-content: space-between; align-items: flex-start;
  margin-bottom: 1.5rem; padding-bottom: 1rem;
  border-bottom: 1px solid rgba(244,239,230,0.1);
}
.day-card-title {
  font-family: var(--font-display); font-size: 1.5rem; font-weight: 400;
  color: var(--snow);
}
.day-card-budget { font-size: 0.8rem; color: var(--marigold); font-weight: 500; }

.day-slots { display: flex; flex-direction: column; gap: 1.2rem; }
.day-slot {
  display: grid; grid-template-columns: 85px 1fr auto;
  gap: 1rem; align-items: center;
}
.slot-time {
  font-size: 0.72rem; font-weight: 600; text-transform: uppercase;
  color: var(--marigold); letter-spacing: 0.05em;
}
.slot-name { font-size: 0.95rem; font-weight: 600; color: var(--snow); margin-bottom: 0.2rem; }
.slot-desc { font-size: 0.8rem; color: rgba(244,239,230,0.65); line-height: 1.45; }
.slot-cost { font-size: 0.85rem; font-weight: 600; color: var(--snow); text-align: right; }

.budget-bar-wrap { margin-top: 1.75rem; }
.budget-bar-label {
  display: flex; justify-content: space-between;
  font-size: 0.75rem; color: rgba(244,239,230,0.7); margin-bottom: 0.4rem;
}
.budget-bar {
  height: 4px; background: rgba(244,239,230,0.12);
  border-radius: 4px; overflow: hidden;
}
.budget-bar-fill {
  height: 100%; background: var(--marigold); border-radius: 4px;
  transition: width 0.4s var(--ease-out);
}
.engine-caption {
  font-size: 0.82rem; color: var(--sage); margin-top: 1rem;
  line-height: 1.5;
}

/* ═══════════════════════════════════════════════════
   FEATURED DESTINATIONS
═══════════════════════════════════════════════════ */
#destinations { background: var(--snow); }
.destinations-inner {
  max-width: 1280px; margin: 0 auto;
  padding: 0 clamp(1.25rem, 5vw, 4rem);
}
.dest-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.75rem;
}
.dest-card {
  background: #FFF; border-radius: 16px; overflow: hidden;
  border: 1px solid rgba(15,26,34,0.08);
  box-shadow: 0 4px 20px rgba(15,26,34,0.04);
  transition: transform 0.35s var(--ease-out), box-shadow 0.35s var(--ease-out);
  position: relative; display: flex; flex-direction: column;
}
.dest-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 16px 36px rgba(15,26,34,0.1);
}
.dest-card-img-wrap {
  position: relative; height: 210px; overflow: hidden;
}
.dest-card-img {
  transition: transform 0.6s var(--ease-soft);
}
.dest-card:hover .dest-card-img { transform: scale(1.05); }

.dest-card-body {
  padding: 1.4rem; flex: 1; display: flex; flex-direction: column;
}
.dest-name {
  font-family: var(--font-display); font-size: 1.6rem; font-weight: 500;
  color: var(--ink); margin-bottom: 0.2rem;
}
.dest-region {
  font-size: 0.78rem; font-weight: 600; text-transform: uppercase;
  color: var(--stone); letter-spacing: 0.06em; margin-bottom: 0.75rem;
}
.dest-meta {
  display: flex; justify-content: space-between; align-items: center;
  margin-top: auto; padding-top: 0.75rem;
  border-top: 1px solid rgba(15,26,34,0.06);
}
.dest-cost { font-size: 0.85rem; font-weight: 600; color: var(--ink); }
.dest-stars { display: flex; gap: 2px; }
.star {
  width: 11px; height: 11px; clip-path: polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%);
  background: var(--stone-lt);
}
.star.filled { background: var(--marigold); }
.dest-hover-cta {
  position: absolute; top: 1rem; right: 1rem;
  background: rgba(15,26,34,0.7); backdrop-filter: blur(4px);
  color: var(--snow); font-size: 0.75rem; font-weight: 600;
  padding: 0.35rem 0.75rem; border-radius: 100px;
  opacity: 0; transform: translateY(-4px);
  transition: opacity 0.2s, transform 0.2s;
}
.dest-card:hover .dest-hover-cta { opacity: 1; transform: none; }

/* ═══════════════════════════════════════════════════
   MAP TEASER
═══════════════════════════════════════════════════ */
#map-teaser {
  background: #1B3029; color: var(--snow);
  overflow: hidden;
}
.map-inner {
  max-width: 1280px; margin: 0 auto;
  padding: 0 clamp(1.25rem, 5vw, 4rem);
  display: grid; grid-template-columns: 1fr 1.2fr;
  gap: clamp(2.5rem, 6vw, 5rem); align-items: center;
}
#map-teaser .section-h2 { color: var(--snow); }
#map-teaser .section-h2 em { color: var(--marigold); }
.map-sub {
  font-size: 1.05rem; line-height: 1.7; color: rgba(244,239,230,0.8);
  font-weight: 300; margin-bottom: 2rem;
}
.map-chips { display: flex; flex-direction: column; gap: 0.75rem; }
.map-chip {
  display: flex; align-items: center; gap: 0.6rem;
  font-size: 0.88rem; color: var(--snow); font-weight: 500;
}
.map-chip-dot {
  width: 7px; height: 7px; border-radius: 50%; background: var(--marigold);
}
.map-visual {
  background: rgba(15,26,34,0.4);
  border: 1px solid rgba(244,239,230,0.15);
  border-radius: 20px; overflow: hidden;
}

/* ═══════════════════════════════════════════════════
   TRAVEL STYLES / INTERESTS
═══════════════════════════════════════════════════ */
#interests {
  background: var(--snow);
  border-bottom: 1px solid rgba(15,26,34,0.06);
}
.interests-header {
  max-width: 1280px; margin: 0 auto 3rem;
  padding: 0 clamp(1.25rem, 5vw, 4rem);
}
.interest-tiles {
  max-width: 1280px; margin: 0 auto;
  padding: 0 clamp(1.25rem, 5vw, 4rem);
  display: grid; grid-template-columns: repeat(3, 1fr);
  gap: 1.5rem;
}
.interest-tile {
  background: #FFF; border-radius: 16px; padding: 2rem;
  border: 1px solid rgba(15,26,34,0.08);
  cursor: pointer; position: relative;
  transition: transform 0.25s var(--ease-out), border-color 0.25s;
  user-select: none;
}
.interest-tile:hover {
  transform: translateY(-4px); border-color: var(--marigold);
}
.interest-tile.selected {
  border-color: var(--marigold);
  background: rgba(200,151,58,0.05);
}
.interest-icon-box {
  width: 44px; height: 44px; border-radius: 12px;
  background: rgba(15,26,34,0.05);
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 1.25rem; color: var(--pine);
}
.interest-tile.selected .interest-icon-box {
  background: var(--marigold); color: var(--ink);
}
.interest-tile-title {
  font-size: 1.15rem; font-weight: 600; color: var(--ink);
  margin-bottom: 0.35rem;
}
.interest-tile-sub {
  font-size: 0.82rem; color: var(--stone); line-height: 1.5;
}
.interest-check {
  position: absolute; top: 1.5rem; right: 1.5rem;
  width: 22px; height: 22px; border-radius: 50%;
  border: 1px solid var(--stone-lt);
  display: flex; align-items: center; justify-content: center;
  font-size: 0.75rem; color: transparent;
}
.interest-tile.selected .interest-check {
  background: var(--marigold); border-color: var(--marigold);
  color: var(--ink); font-weight: 700;
}
.interests-cta {
  max-width: 1280px; margin: 2.5rem auto 0;
  padding: 0 clamp(1.25rem, 5vw, 4rem);
  display: flex; justify-content: center;
}

/* ═══════════════════════════════════════════════════
   TRENDING ITINERARIES
═══════════════════════════════════════════════════ */
#trending { background: var(--paper); }
.trending-inner {
  max-width: 1280px; margin: 0 auto;
  padding: 0 clamp(1.25rem, 5vw, 4rem);
}
.tickets-row {
  display: grid; grid-template-columns: repeat(3, 1fr);
  gap: 1.75rem;
}
.ticket {
  background: #FFF; border-radius: 16px; overflow: hidden;
  box-shadow: 0 4px 20px rgba(15,26,34,0.05);
  border: 1px solid rgba(15,26,34,0.06);
  display: flex; flex-direction: column;
}
.ticket-header {
  padding: 1.25rem; background: var(--ink); color: var(--snow);
  display: flex; justify-content: space-between; align-items: center;
}
.ticket-dest { font-family: var(--font-display); font-size: 1.15rem; font-weight: 400; }
.ticket-days {
  font-size: 0.75rem; font-weight: 600; padding: 0.2rem 0.55rem;
  border-radius: 100px; background: rgba(244,239,230,0.15);
}
.ticket-perf {
  height: 8px;
  background-image: radial-gradient(circle, var(--paper) 4px, transparent 5px);
  background-size: 16px 8px; margin-top: -4px;
}
.ticket-body { padding: 1.4rem; flex: 1; }
.ticket-route {
  display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap;
  margin-bottom: 1.25rem;
}
.ticket-city {
  font-size: 0.85rem; font-weight: 600; color: var(--ink);
}
.ticket-arrow {
  width: 12px; height: 1px; background: var(--stone); position: relative;
}
.ticket-arrow::after {
  content: ''; position: absolute; right: 0; top: -3px;
  width: 0; height: 0; border-top: 3px solid transparent;
  border-bottom: 3px solid transparent; border-left: 4px solid var(--stone);
}
.ticket-meta { display: flex; gap: 0.5rem; }
.ticket-chip {
  font-size: 0.75rem; padding: 0.2rem 0.55rem; border-radius: 6px;
  background: rgba(15,26,34,0.05); color: var(--slate); font-weight: 500;
}
.ticket-chip.budget { background: rgba(200,151,58,0.12); color: var(--marigold); }
.ticket-footer {
  padding: 1rem 1.4rem; border-top: 1px dashed rgba(15,26,34,0.1);
  display: flex; justify-content: space-between; align-items: center;
}
.ticket-saves { font-size: 0.8rem; color: var(--stone); }
.ticket-use {
  font-size: 0.82rem; font-weight: 600; color: var(--pine);
  transition: color 0.15s;
}
.ticket-use:hover { color: var(--marigold); }

/* ═══════════════════════════════════════════════════
   STATS SECTION
═══════════════════════════════════════════════════ */
#stats {
  background: var(--snow);
  border-bottom: 1px solid rgba(15,26,34,0.06);
  padding: clamp(3.5rem, 6vw, 5.5rem) 0;
}
.stats-inner {
  max-width: 1280px; margin: 0 auto;
  padding: 0 clamp(1.25rem, 5vw, 4rem);
  display: grid; grid-template-columns: repeat(4, 1fr);
  gap: 2rem; text-align: center;
}
.stat-item {
  display: flex; flex-direction: column; align-items: center;
}
.stat-num {
  font-family: var(--font-display);
  font-size: clamp(2.8rem, 5vw, 4.2rem);
  font-weight: 300; color: var(--ink); line-height: 1;
  margin-bottom: 0.4rem;
}
.stat-num span { color: var(--marigold); font-size: 0.75em; margin-left: 2px; }
.stat-label {
  font-size: 0.82rem; font-weight: 600; text-transform: uppercase;
  letter-spacing: 0.08em; color: var(--stone);
}

/* ═══════════════════════════════════════════════════
   STORIES / REVIEWS
═══════════════════════════════════════════════════ */
#stories {
  background: var(--snow); position: relative; overflow: hidden;
}
.stories-watermark {
  position: absolute; right: 5%; top: 50%; transform: translateY(-50%);
  font-family: var(--font-deva); font-size: clamp(10rem, 25vw, 22rem);
  color: rgba(15,26,34,0.03); pointer-events: none; user-select: none;
  line-height: 1;
}
.stories-inner {
  max-width: 860px; margin: 0 auto;
  padding: 0 clamp(1.25rem, 5vw, 4rem);
  text-align: center; position: relative; z-index: 2;
}
.stories-inner .section-eyebrow { justify-content: center; }
.quote-slider {
  margin: 3rem 0 2rem; min-height: 160px; position: relative;
}
.quote-slide {
  position: absolute; inset: 0; opacity: 0;
  transition: opacity 0.5s var(--ease-soft);
  display: flex; flex-direction: column; align-items: center;
  pointer-events: none;
}
.quote-slide.active { opacity: 1; pointer-events: all; position: relative; }
.quote-text {
  font-family: var(--font-display);
  font-size: clamp(1.3rem, 2.8vw, 1.85rem);
  font-weight: 300; font-style: italic; line-height: 1.5;
  color: var(--ink); margin-bottom: 1.75rem;
}
.quote-author {
  display: flex; align-items: center; gap: 0.85rem;
}
.quote-avatar {
  width: 40px; height: 40px; border-radius: 50%;
  background: var(--pine); color: var(--snow);
  display: flex; align-items: center; justify-content: center;
  font-weight: 600; font-size: 0.85rem;
}
.quote-meta { text-align: left; }
.quote-name { font-size: 0.95rem; font-weight: 600; color: var(--ink); }
.quote-trip { font-size: 0.8rem; color: var(--stone); }

.quote-nav {
  display: flex; justify-content: center; gap: 0.5rem; margin-top: 1.5rem;
}
.quote-dot {
  width: 8px; height: 8px; border-radius: 50%;
  background: rgba(15,26,34,0.15); cursor: pointer;
  transition: background 0.2s, transform 0.2s;
}
.quote-dot.active { background: var(--marigold); transform: scale(1.3); }

/* ═══════════════════════════════════════════════════
   FINAL CTA
═══════════════════════════════════════════════════ */
#final-cta {
  background: #0F1A22; color: var(--snow);
  text-align: center; overflow: hidden;
  padding: clamp(6rem, 12vw, 10rem) 0 clamp(4rem, 8vw, 6rem);
}
.cta-ridges {
  position: absolute; top: 0; left: 0; right: 0;
  pointer-events: none; opacity: 0.4;
}
.cta-inner {
  position: relative; z-index: 5;
  max-width: 720px; margin: 0 auto;
  padding: 0 clamp(1.25rem, 5vw, 2rem);
}
.cta-h2 {
  font-family: var(--font-display);
  font-size: clamp(2.8rem, 6.5vw, 4.8rem);
  font-weight: 300; line-height: 1.08; margin-bottom: 1.25rem;
}
.cta-h2 em { font-style: italic; color: var(--marigold); }
.cta-sub {
  font-size: 1.15rem; color: rgba(244,239,230,0.7);
  font-weight: 300; margin-bottom: 2.5rem;
}
.cta-btns {
  display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;
}

/* ═══════════════════════════════════════════════════
   FOOTER
═══════════════════════════════════════════════════ */
#footer {
  background: #091218; color: var(--snow);
  padding: 5rem 0 2rem; border-top: 1px solid rgba(244,239,230,0.08);
  position: relative;
}
.footer-deva {
  position: absolute; top: -1.5rem; left: 50%; transform: translateX(-50%);
  font-family: var(--font-deva); font-size: 1.2rem;
  color: var(--marigold); opacity: 0.6;
}
.footer-inner {
  max-width: 1280px; margin: 0 auto;
  padding: 0 clamp(1.25rem, 5vw, 4rem);
  display: grid; grid-template-columns: 2fr 1fr 1fr 1fr;
  gap: 3rem; margin-bottom: 4rem;
}
.footer-brand img { width: 130px; height: auto; margin-bottom: 1rem; }
.footer-slogan {
  font-family: var(--font-display); font-size: 1.25rem; font-style: italic;
  color: rgba(244,239,230,0.7); margin-bottom: 1.25rem;
}
.footer-col h4 {
  font-size: 0.8rem; font-weight: 600; text-transform: uppercase;
  letter-spacing: 0.1em; color: var(--marigold); margin-bottom: 1.25rem;
}
.footer-col ul { list-style: none; display: flex; flex-direction: column; gap: 0.65rem; }
.footer-col a {
  font-size: 0.88rem; color: rgba(244,239,230,0.65);
  transition: color 0.15s;
}
.footer-col a:hover { color: var(--snow); }

.footer-bottom {
  max-width: 1280px; margin: 0 auto;
  padding: 2rem clamp(1.25rem, 5vw, 4rem) 0;
  border-top: 1px solid rgba(244,239,230,0.06);
  display: flex; justify-content: space-between; align-items: center;
  font-size: 0.78rem; color: rgba(244,239,230,0.4);
  flex-wrap: wrap; gap: 1rem;
}

/* ═══════════════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════════════ */
@media (max-width: 1024px) {
  .how-inner, .engine-inner, .map-inner { grid-template-columns: 1fr; }
  .dest-grid { grid-template-columns: 1fr 1fr; }
  .interest-tiles { grid-template-columns: 1fr 1fr; }
  .tickets-row { grid-template-columns: 1fr 1fr; }
  .stats-inner { grid-template-columns: repeat(2,1fr); gap: 3rem; }
  .footer-inner { grid-template-columns: 1fr 1fr; }
}

@media (max-width: 768px) {
  .nav-links, .nav-actions { display: none; }
  #hamburger { display: flex; }
  .dest-grid { grid-template-columns: 1fr; }
  .interest-tiles { grid-template-columns: 1fr; }
  .tickets-row { grid-template-columns: 1fr; }
  .stats-inner { grid-template-columns: repeat(2,1fr); }
  .footer-inner { grid-template-columns: 1fr; gap: 2rem; }
  .qs-field:nth-child(3) { display: none; }
  #quick-search { flex-wrap: wrap; }
  .qs-cta { width: 100%; justify-content: center; padding: 1rem; }
  .section-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
}

@media (max-width: 480px) {
  .stats-inner { grid-template-columns: 1fr 1fr; gap: 2rem; }
  .cta-btns { flex-direction: column; align-items: center; }
  .hero-caption { display: none; }
  .season-widget { display: none; }
}

/* prefers-reduced-motion */
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
  .reveal { opacity: 1; transform: none; }
  #intro { display: none; }
  #nav-logo { visibility: visible; }
}
</style>
</head>
<body class="intro-active">

<a href="#main" class="skip-link">Skip to content</a>
<div id="scroll-progress" aria-hidden="true"></div>

<!-- ═══════════════════════════════════════════════
     INTRO OVERLAY
═══════════════════════════════════════════════ -->
<div id="intro" aria-hidden="true" role="presentation">
  <div id="intro-logo-wrap">
    <img id="intro-logo-img" src="logo.svg" alt="YatraPath"
      onerror="this.style.display='none'; document.getElementById('intro-logo-text').style.display='block'">
    <div id="intro-logo-text" style="
      font-family:'Cormorant Garamond',Georgia,serif;
      font-size:2.8rem; font-weight:300; font-style:italic;
      color:#F4EFE6; letter-spacing:0.04em; text-align:center;">
      YatraPath
    </div>
  </div>
  <div id="intro-skip">click or press any key to skip</div>
</div>

<!-- ═══════════════════════════════════════════════
     NAVBAR
═══════════════════════════════════════════════ -->
<header id="navbar" role="banner">
  <div class="nav-inner">
    <a href="homepage.php" id="nav-logo" aria-label="YatraPath home">
      <img src="logo.svg" alt="YatraPath"
        onerror="this.outerHTML='<span style=\'font-family:var(--font-display);font-size:1.3rem;font-weight:400;font-style:italic;color:var(--snow)\'>YatraPath</span>'">
    </a>
    
    <nav class="nav-links" aria-label="Main navigation">
      <a href="#destinations">Destinations</a>
      <a href="#how">How it works</a>
      <a href="#engine">Engine</a>
      <a href="#trending">Itineraries</a>
      <a href="#stories">Reviews</a>
    </nav>

    <div class="nav-actions">
      <?php if ($userLoggedIn): ?>
        <?php if ($userIsAdmin): ?>
          <a href="admin/dashboard.php" class="nav-login">Admin Portal</a>
          <a href="admin/destinations.php" class="btn-primary">Manage Data</a>
          <a href="api/auth/logout.php" class="nav-login" style="font-size:0.8rem;opacity:0.75" title="Sign out">Sign Out</a>
        <?php else: ?>
          <a href="user/dashboard.php" class="nav-login" title="Welcome, <?= htmlspecialchars($userName) ?>">Dashboard</a>
          <a href="user/itineraries.php" class="nav-login">My Trips</a>
          <a href="user/generator.php" class="btn-primary">Create Itinerary</a>
          <a href="api/auth/logout.php" class="nav-login" style="font-size:0.8rem;opacity:0.75" title="Sign out">Sign Out</a>
        <?php endif; ?>
      <?php else: ?>
        <a href="user/index.php?tab=login" class="nav-login">Log in</a>
        <a href="user/index.php?tab=register" class="btn-primary">Get Started</a>
      <?php endif; ?>
    </div>

    <button id="hamburger" aria-label="Open menu" aria-expanded="false" aria-controls="mobile-menu">
      <span></span><span></span><span></span>
    </button>
  </div>
</header>

<!-- Mobile menu -->
<nav id="mobile-menu" aria-label="Mobile navigation" aria-hidden="true">
  <button id="menu-close" aria-label="Close menu">✕</button>
  <a href="#destinations" class="mobile-link">Destinations</a>
  <a href="#how" class="mobile-link">How it works</a>
  <a href="#engine" class="mobile-link">Algorithm Engine</a>
  <a href="#trending" class="mobile-link">Itineraries</a>
  <a href="#stories" class="mobile-link">Reviews</a>
  
  <?php if ($userLoggedIn): ?>
    <?php if ($userIsAdmin): ?>
      <a href="admin/dashboard.php" class="mobile-link">Admin Portal</a>
    <?php else: ?>
      <a href="user/dashboard.php" class="mobile-link">Dashboard</a>
      <a href="user/itineraries.php" class="mobile-link">My Trips</a>
      <a href="user/generator.php" class="mobile-link" style="color:var(--marigold)">Create Itinerary</a>
    <?php endif; ?>
    <a href="api/auth/logout.php" class="mobile-link" style="font-size:1.4rem;color:var(--stone)">Sign Out</a>
  <?php else: ?>
    <a href="user/index.php?tab=login" class="mobile-link">Log in</a>
    <a href="user/index.php?tab=register" class="mobile-link" style="color:var(--marigold)">Get Started</a>
  <?php endif; ?>
</nav>

<!-- ═══════════════════════════════════════════════
     MAIN CONTENT
═══════════════════════════════════════════════ -->
<main id="main">

<!-- HERO -->
<section id="hero" aria-label="Hero">

  <!-- Hero slideshow -->
  <div class="hero-slides" aria-hidden="true">
    <div class="hero-slide active" id="slide-0" style="background-color:#1a2a38;background-image:linear-gradient(135deg,#0F1A22 0%,#1E3A34 50%,#2B3A45 100%)"></div>
    <div class="hero-slide"       id="slide-1" style="background-color:#1e2d38;background-image:linear-gradient(135deg,#1E3A34 0%,#2B3A45 60%,#1a3028 100%)"></div>
    <div class="hero-slide"       id="slide-2" style="background-color:#1a2830;background-image:linear-gradient(135deg,#2B3A45 0%,#1E3A34 50%,#0F1A22 100%)"></div>
    <div class="hero-slide"       id="slide-3" style="background-color:#20302a;background-image:linear-gradient(135deg,#1E3A34 0%,#0F1A22 40%,#2B3A45 100%)"></div>
  </div>

  <!-- Atmosphere layers -->
  <div class="hero-overlay" aria-hidden="true"></div>
  <div class="hero-overlay-bottom" aria-hidden="true"></div>
  <div class="mist" aria-hidden="true">
    <div class="mist-band"></div>
    <div class="mist-band"></div>
  </div>

  <!-- Prayer flags SVG -->
  <div class="flags-wrap" aria-hidden="true">
    <svg viewBox="0 0 260 60" xmlns="http://www.w3.org/2000/svg" width="260">
      <line x1="0" y1="8" x2="260" y2="18" stroke="rgba(200,151,58,0.3)" stroke-width="0.5"/>
      <rect x="10" y="8" width="22" height="30" fill="rgba(180,83,58,0.45)" rx="1"/>
      <rect x="44" y="10" width="22" height="30" fill="rgba(200,151,58,0.45)" rx="1"/>
      <rect x="78" y="11" width="22" height="30" fill="rgba(244,239,230,0.25)" rx="1"/>
      <rect x="112" y="12" width="22" height="30" fill="rgba(143,165,138,0.45)" rx="1"/>
      <rect x="146" y="13" width="22" height="30" fill="rgba(30,58,52,0.55)" rx="1"/>
      <rect x="180" y="14" width="22" height="30" fill="rgba(180,83,58,0.4)" rx="1"/>
      <rect x="214" y="15" width="22" height="30" fill="rgba(200,151,58,0.4)" rx="1"/>
    </svg>
  </div>

  <!-- Season widget -->
  <div class="season-widget">
    <div class="season-widget-inner">
      <div class="season-time" id="ktm-time">--:--</div>
      <div class="season-name" id="season-name">Autumn</div>
      <div class="season-tip" id="season-tip">Best season for trekking</div>
    </div>
  </div>

  <!-- Hero content -->
  <div class="hero-content">
    <p class="hero-eyebrow" id="hero-eyebrow" aria-hidden="true">
      यात्रा पथ · Nepal, planned for you
    </p>

    <h1 class="hero-h1">
      <span class="hero-h1-inner" id="hero-h1">
        Let the path<br><em>find</em> you.
      </span>
    </h1>

    <div class="hero-ticker" id="hero-ticker">
      <span class="ticker-label">Explore</span>
      <div class="ticker-slot" aria-live="off" aria-atomic="true">
        <div class="ticker-word current" id="tick-0">Annapurna dawns</div>
        <div class="ticker-word next"    id="tick-1">Kathmandu alleys</div>
        <div class="ticker-word next"    id="tick-2">Chitwan mornings</div>
        <div class="ticker-word next"    id="tick-3">Phewa Lake evenings</div>
      </div>
      <span class="sr-only">Annapurna dawns, Kathmandu alleys, Chitwan mornings, Phewa Lake evenings</span>
    </div>

    <p class="hero-sub" id="hero-sub">
      Tell us where, how long and your NPR budget. YatraPath builds a personal morning-to-evening itinerary across Nepal in under three seconds.
    </p>

    <!-- Quick search -->
    <div id="quick-search" role="search" aria-label="Plan your trip">
      <div class="qs-field">
        <label class="qs-label" for="qs-dest">Destination</label>
        <input class="qs-input" id="qs-dest" type="text"
          placeholder="Pokhara, Chitwan, Mustang…" autocomplete="off">
      </div>
      <div class="qs-field" style="max-width:130px">
        <label class="qs-label">Days</label>
        <div class="qs-stepper">
          <button id="days-minus" type="button" aria-label="Decrease days">−</button>
          <span class="qs-days-val" id="days-val">5</span>
          <button id="days-plus" type="button" aria-label="Increase days">+</button>
        </div>
      </div>
      <div class="qs-field">
        <label class="qs-label" for="qs-budget">Budget (NPR)</label>
        <input class="qs-input" id="qs-budget" type="number"
          placeholder="25,000" min="3000" step="1000" value="25000">
        <div class="qs-meta">
          <span class="qs-per-day" id="qs-perday"></span>
          <span class="qs-tier" id="qs-tier"></span>
        </div>
      </div>
      <button class="qs-cta" id="qs-submit" type="button" aria-label="Generate itinerary">
        Generate
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
          <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
    </div>
  </div><!-- /hero-content -->

  <!-- Hero photo caption -->
  <div class="hero-caption" aria-hidden="true">
    <div class="hero-caption-text" id="hero-caption-text">Annapurna · Gandaki · 28.5937° N</div>
    <div class="hero-progress">
      <div class="hero-progress-bar" id="hero-progress-bar"></div>
    </div>
  </div>

  <!-- Scroll cue -->
  <div class="scroll-cue" id="scroll-cue" aria-hidden="true">
    <div class="scroll-cue-line"></div>
    <span>Scroll</span>
  </div>

  <!-- Ridgeline SVG curve at bottom -->
  <div class="hero-curve" aria-hidden="true">
    <svg viewBox="0 0 1440 80" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M0,80 L0,55 C80,30 120,20 200,35 C280,50 340,25 440,18 C540,11 580,28 660,32 C740,36 800,20 900,15 C1000,10 1060,28 1160,38 C1260,48 1340,30 1440,20 L1440,80 Z"
        fill="#F4EFE6"/>
    </svg>
  </div>

  <!-- Trust strip -->
  <div class="trust-strip" role="list">
    <div class="trust-item" role="listitem"><span class="trust-dot"></span>Season-aware</div>
    <div class="trust-item" role="listitem"><span class="trust-dot"></span>Budget-smart</div>
    <div class="trust-item" role="listitem"><span class="trust-dot"></span>Route-optimised</div>
    <div class="trust-item" role="listitem"><span class="trust-dot"></span>PDF Export Ready</div>
  </div>

</section><!-- /hero -->


<!-- MARQUEE -->
<section id="marquee" aria-hidden="true">
  <div class="marquee-track">
    <span class="marquee-item">Kathmandu <span class="marquee-glyph">✦</span></span>
    <span class="marquee-item">Pokhara <span class="marquee-glyph">✦</span></span>
    <span class="marquee-item">Chitwan <span class="marquee-glyph">✦</span></span>
    <span class="marquee-item">Lumbini <span class="marquee-glyph">✦</span></span>
    <span class="marquee-item">Bhaktapur <span class="marquee-glyph">✦</span></span>
    <span class="marquee-item">Nagarkot <span class="marquee-glyph">✦</span></span>
    <span class="marquee-item">Mustang <span class="marquee-glyph">✦</span></span>
    <span class="marquee-item">Langtang <span class="marquee-glyph">✦</span></span>
    <span class="marquee-item">Ilam <span class="marquee-glyph">✦</span></span>
    <span class="marquee-item">Boudhanath <span class="marquee-glyph">✦</span></span>
    <span class="marquee-item">Kathmandu <span class="marquee-glyph">✦</span></span>
    <span class="marquee-item">Pokhara <span class="marquee-glyph">✦</span></span>
    <span class="marquee-item">Chitwan <span class="marquee-glyph">✦</span></span>
    <span class="marquee-item">Lumbini <span class="marquee-glyph">✦</span></span>
    <span class="marquee-item">Bhaktapur <span class="marquee-glyph">✦</span></span>
    <span class="marquee-item">Nagarkot <span class="marquee-glyph">✦</span></span>
    <span class="marquee-item">Mustang <span class="marquee-glyph">✦</span></span>
    <span class="marquee-item">Langtang <span class="marquee-glyph">✦</span></span>
    <span class="marquee-item">Ilam <span class="marquee-glyph">✦</span></span>
    <span class="marquee-item">Boudhanath <span class="marquee-glyph">✦</span></span>
  </div>
</section>


<!-- HOW IT WORKS -->
<section id="how" aria-labelledby="how-h2">
  <div class="how-inner">
    <div class="how-left">
      <p class="section-eyebrow reveal">Three steps, one path</p>
      <h2 class="section-h2 reveal reveal-delay-1" id="how-h2">
        Your Nepal plan,<br><em>built in seconds</em>
      </h2>
      <div class="how-steps">
        <div class="how-path-line" aria-hidden="true"></div>
        <div class="how-step reveal">
          <div class="how-step-num" aria-hidden="true">01</div>
          <div class="how-step-body">
            <h3>Set your preferences</h3>
            <p>Pick destinations, travel dates, how long you have, your budget in NPR and what you love doing.</p>
          </div>
        </div>
        <div class="how-step reveal reveal-delay-1">
          <div class="how-step-num" aria-hidden="true">02</div>
          <div class="how-step-body">
            <h3>Generate the plan</h3>
            <p>Our engine detects the season, filters unsuitable routes, scores your interests, respects your budget and orders the journey by the Haversine distance.</p>
          </div>
        </div>
        <div class="how-step reveal reveal-delay-2">
          <div class="how-step-num" aria-hidden="true">03</div>
          <div class="how-step-body">
            <h3>Travel</h3>
            <p>Save the itinerary, share it with your group or download a clean PDF. Then go.</p>
          </div>
        </div>
      </div>
    </div>
    
    <div class="how-right reveal reveal-delay-2">
      <div class="how-panel">
        <div style="font-size:0.7rem;color:var(--stone);letter-spacing:0.08em;text-transform:uppercase;margin-bottom:1rem">Sample Day Schedule · Pokhara</div>
        <div class="mock-card">
          <div class="mock-card-row">
            <span class="mock-time">Morning</span>
            <span class="mock-act">Sarangkot sunrise viewpoint</span>
            <span class="mock-cost">Free</span>
          </div>
          <div class="mock-card-row">
            <span class="mock-time">Afternoon</span>
            <span class="mock-act">Phewa Lake boating & Tal Barahi</span>
            <span class="mock-cost">NPR 500</span>
          </div>
          <div class="mock-card-row">
            <span class="mock-time">Evening</span>
            <span class="mock-act">Lakeside cultural food tour</span>
            <span class="mock-cost">NPR 800</span>
          </div>
        </div>
        <div style="margin-top:1.25rem;display:flex;gap:0.75rem;align-items:center">
          <div style="flex:1;height:5px;background:rgba(163,155,141,0.18);border-radius:4px;overflow:hidden">
            <div style="width:45%;height:100%;background:var(--marigold);border-radius:4px"></div>
          </div>
          <span style="font-size:0.75rem;color:var(--stone);font-weight:600">NPR 1,300 / 3,500 daily spend</span>
        </div>
      </div>
    </div>
  </div>
</section>


<!-- ENGINE DEMO (INTERACTIVE ALGORITHM) -->
<section id="engine" aria-labelledby="engine-h2">
  <div class="engine-bg-blur" aria-hidden="true"></div>
  <div class="engine-inner">
    <div class="engine-controls">
      <p class="section-eyebrow reveal">Why the plan feels human</p>
      <h2 class="section-h2 reveal reveal-delay-1" id="engine-h2">
        Watch the<br><em>algorithm work</em>
      </h2>
      <p style="font-size:0.92rem;color:var(--stone);line-height:1.7;margin-bottom:2rem" class="reveal reveal-delay-2">
        Adjust the controls. The itinerary rebuilds in real time — using the exact same constrained optimization rules executed by our backend.
      </p>

      <div class="engine-control-group reveal">
        <div class="engine-label">Season</div>
        <div class="season-tabs" role="group" aria-label="Select season">
          <button type="button" class="season-tab active" data-season="spring">Spring</button>
          <button type="button" class="season-tab" data-season="monsoon">Monsoon</button>
          <button type="button" class="season-tab" data-season="autumn">Autumn</button>
          <button type="button" class="season-tab" data-season="winter">Winter</button>
        </div>
      </div>

      <div class="engine-control-group reveal reveal-delay-1">
        <div class="engine-label">Budget — <span id="budget-display-label">NPR 35,000</span></div>
        <div class="budget-slider-wrap">
          <input type="range" id="budget-slider" min="8000" max="100000" step="1000" value="35000"
            aria-label="Budget in NPR">
        </div>
        <div class="budget-display">
          <span class="budget-val" id="budget-val">NPR 35,000</span>
          <span class="budget-tier-chip" id="budget-tier">Mid-range</span>
        </div>
      </div>

      <div class="engine-control-group reveal reveal-delay-2">
        <div class="engine-label">Interests</div>
        <div class="interest-chips" role="group" aria-label="Select interests">
          <button type="button" class="interest-chip selected" data-int="adventure">Adventure</button>
          <button type="button" class="interest-chip selected" data-int="cultural">Cultural</button>
          <button type="button" class="interest-chip" data-int="nature">Nature</button>
          <button type="button" class="interest-chip" data-int="food">Food</button>
          <button type="button" class="interest-chip" data-int="wellness">Wellness</button>
          <button type="button" class="interest-chip" data-int="photo">Photography</button>
        </div>
      </div>

      <!-- Algorithm stepper -->
      <div class="algo-stepper reveal reveal-delay-3">
        <div class="algo-step" id="algo-0"><div class="algo-step-dot"></div>1. Season detection</div>
        <div class="algo-step" id="algo-1"><div class="algo-step-dot"></div>2. Seasonal activity pruning</div>
        <div class="algo-step" id="algo-2"><div class="algo-step-dot"></div>3. Weighted interest scoring</div>
        <div class="algo-step" id="algo-3"><div class="algo-step-dot"></div>4. Budget tier partitioning</div>
        <div class="algo-step" id="algo-4"><div class="algo-step-dot"></div>5. Haversine route sequencing</div>
        <div class="algo-step" id="algo-5"><div class="algo-step-dot"></div>6. Greedy time-slot filling</div>
        <div class="algo-status" id="algo-status"></div>
      </div>
    </div>

    <!-- Result card -->
    <div class="engine-result reveal reveal-delay-1">
      <div class="day-card" id="demo-card">
        <div class="day-card-header">
          <span class="day-card-title" id="demo-title">Day 1 · Kathmandu</span>
          <span class="day-card-budget" id="demo-budget">Budget: NPR 35,000 · 5 days</span>
        </div>
        <div class="day-slots" id="demo-slots">
          <div class="day-slot">
            <span class="slot-time">Morning</span>
            <div>
              <div class="slot-name" id="slot-0-name">Boudhanath Stupa</div>
              <div class="slot-desc" id="slot-0-desc">Circle the world's largest stupa at dawn, prayer wheels still warm.</div>
            </div>
            <span class="slot-cost" id="slot-0-cost">NPR 200</span>
          </div>
          <div class="day-slot">
            <span class="slot-time">Afternoon</span>
            <div>
              <div class="slot-name" id="slot-1-name">Pashupatinath Temple</div>
              <div class="slot-desc" id="slot-1-desc">Nepal's holiest Hindu temple; arrive before the evening aarti.</div>
            </div>
            <span class="slot-cost" id="slot-1-cost">NPR 1,000</span>
          </div>
          <div class="day-slot">
            <span class="slot-time">Evening</span>
            <div>
              <div class="slot-name" id="slot-2-name">Thamel Night Walk</div>
              <div class="slot-desc" id="slot-2-desc">Wander the bazaar, try steaming momos and butter tea at a local stall.</div>
            </div>
            <span class="slot-cost" id="slot-2-cost">NPR 600</span>
          </div>
        </div>
        <div class="budget-bar-wrap">
          <div class="budget-bar-label">
            <span>Day spend</span>
            <span id="demo-spend-label">NPR 1,800 / 7,000</span>
          </div>
          <div class="budget-bar">
            <div class="budget-bar-fill" id="demo-bar" style="width:26%"></div>
          </div>
        </div>
      </div>
      <div class="engine-caption" id="engine-caption">
        Autumn detected — clear skies, ideal for trekking and temple visits.
      </div>
      <div style="margin-top:1.5rem">
        <a href="user/generator.php" class="btn-primary" style="display:inline-flex;text-decoration:none">
          Try it with your own trip
          <svg width="14" height="14" viewBox="0 0 14 14" fill="none" style="margin-left:4px">
            <path d="M2 7h10M8 3l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </a>
      </div>
    </div>
  </div>
</section>


<!-- FEATURED DESTINATIONS -->
<section id="destinations" aria-labelledby="dest-h2">
  <div class="destinations-inner">
    <div class="section-header">
      <div>
        <p class="section-eyebrow reveal">Top-rated by travellers</p>
        <h2 class="section-h2 reveal reveal-delay-1" id="dest-h2">Featured<br><em>destinations</em></h2>
      </div>
      <a href="user/generator.php" class="view-all reveal reveal-delay-2">Plan your itinerary →</a>
    </div>

    <div class="dest-grid" id="dest-grid">
      <!-- Destination cards rendered by JS -->
    </div>
  </div>
</section>


<!-- MAP TEASER -->
<section id="map-teaser" aria-labelledby="map-h2">
  <div class="map-inner">
    <div class="map-copy">
      <p class="section-eyebrow reveal">Haversine route optimisation</p>
      <h2 class="section-h2 reveal reveal-delay-1" id="map-h2">
        Routes ordered so you<br><em>explore</em>, not commute
      </h2>
      <p class="map-sub reveal reveal-delay-2">
        YatraPath connects destinations in the nearest-neighbour order that minimises transit fatigue, so each day starts where the last one ended.
      </p>
      <div class="map-chips reveal reveal-delay-3">
        <div class="map-chip"><span class="map-chip-dot"></span>Kathmandu → Pokhara → Chitwan</div>
        <div class="map-chip"><span class="map-chip-dot"></span>~480 km · 7 days</div>
        <div class="map-chip"><span class="map-chip-dot"></span>Est. NPR 42,000</div>
      </div>
    </div>
    
    <div class="map-visual reveal reveal-delay-1">
      <svg id="nepal-svg" viewBox="0 0 560 200" xmlns="http://www.w3.org/2000/svg"
        style="width:100%;height:100%;padding:1.5rem" aria-label="Map of Nepal showing sample route">
        <path d="M30,120 C60,90 90,70 130,60 C170,50 210,55 260,50 C310,45 360,35 410,40 C450,44 490,55 530,70 L530,140 C490,145 450,140 410,138 C370,136 340,145 300,148 C260,151 220,145 180,148 C140,151 100,150 70,145 Z"
          fill="none" stroke="rgba(244,239,230,0.18)" stroke-width="1.5"/>
        <path id="route-path"
          d="M140,90 C160,85 180,80 200,78 C240,88 270,82 310,80 C340,85 370,95 400,100"
          fill="none" stroke="var(--marigold)" stroke-width="2"
          stroke-dasharray="200" stroke-dashoffset="200"
          stroke-linecap="round"/>
        <g id="pin-ktm">
          <circle cx="140" cy="90" r="5" fill="var(--marigold)" opacity="0"/>
          <circle cx="140" cy="90" r="12" fill="none" stroke="rgba(200,151,58,0.3)" stroke-width="1" opacity="0"/>
          <text x="140" y="76" text-anchor="middle" fill="rgba(244,239,230,0.85)" font-size="10" font-family="Inter,sans-serif">Kathmandu</text>
        </g>
        <g id="pin-pkr">
          <circle cx="270" cy="82" r="5" fill="var(--marigold)" opacity="0"/>
          <circle cx="270" cy="82" r="12" fill="none" stroke="rgba(200,151,58,0.3)" stroke-width="1" opacity="0"/>
          <text x="270" y="68" text-anchor="middle" fill="rgba(244,239,230,0.85)" font-size="10" font-family="Inter,sans-serif">Pokhara</text>
        </g>
        <g id="pin-cht">
          <circle cx="400" cy="100" r="5" fill="var(--marigold)" opacity="0"/>
          <circle cx="400" cy="100" r="12" fill="none" stroke="rgba(200,151,58,0.3)" stroke-width="1" opacity="0"/>
          <text x="400" y="86" text-anchor="middle" fill="rgba(244,239,230,0.85)" font-size="10" font-family="Inter,sans-serif">Chitwan</text>
        </g>
      </svg>
    </div>
  </div>
</section>


<!-- INTERESTS CAROUSEL -->
<section id="interests" aria-labelledby="interests-h2">
  <div class="interests-header">
    <div>
      <p class="section-eyebrow reveal">Choose your vibe</p>
      <h2 class="section-h2 reveal reveal-delay-1" id="interests-h2">
        What draws<br><em>you to Nepal?</em>
      </h2>
    </div>
  </div>
  
  <div class="interest-tiles" role="list" id="interest-tiles">
    <!-- Tiles injected by JS -->
  </div>
  
  <div class="interests-cta">
    <button type="button" class="btn-primary" id="interests-cta-btn" style="opacity:0.4;cursor:default" disabled>
      Start with these interests
      <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
        <path d="M2 7h10M8 3l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
    </button>
  </div>
</section>


<!-- TRENDING ITINERARIES -->
<section id="trending" aria-labelledby="trending-h2">
  <div class="trending-inner">
    <div class="section-header">
      <div>
        <p class="section-eyebrow reveal">Most saved this month</p>
        <h2 class="section-h2 reveal reveal-delay-1" id="trending-h2">
          Trending<br><em>itineraries</em>
        </h2>
      </div>
    </div>
    <div class="tickets-row" id="tickets-row">
      <!-- Injected by JS -->
    </div>
  </div>
</section>


<!-- STATS -->
<section id="stats" aria-labelledby="stats-h2">
  <div class="stats-inner">
    <div class="stat-item reveal">
      <div class="stat-num" id="stat-dest"><?= $stats['dest'] ?><span>+</span></div>
      <div class="stat-label">Destinations</div>
    </div>
    <div class="stat-item reveal reveal-delay-1">
      <div class="stat-num" id="stat-act"><?= $stats['act'] ?><span>+</span></div>
      <div class="stat-label">Activities Mapped</div>
    </div>
    <div class="stat-item reveal reveal-delay-2">
      <div class="stat-num" id="stat-plans"><?= $stats['plans'] ?><span>+</span></div>
      <div class="stat-label">Itineraries Generated</div>
    </div>
    <div class="stat-item reveal reveal-delay-3">
      <div class="stat-num" id="stat-reviews"><?= $stats['reviews'] ?><span>+</span></div>
      <div class="stat-label">Traveller Reviews</div>
    </div>
  </div>
  <h2 class="sr-only" id="stats-h2">YatraPath by the numbers</h2>
</section>


<!-- STORIES -->
<section id="stories" aria-labelledby="stories-h2">
  <div class="stories-watermark" aria-hidden="true">यात्रा</div>
  <div class="stories-inner">
    <p class="section-eyebrow reveal">Traveller stories</p>
    <h2 class="section-h2 reveal reveal-delay-1" id="stories-h2">
      From the<br><em>people who went</em>
    </h2>
    <div class="quote-slider reveal reveal-delay-2" id="quote-slider">
      <!-- Injected by JS -->
    </div>
    <div class="quote-nav" id="quote-nav" aria-label="Reviews pagination"></div>
  </div>
</section>


<!-- FINAL CTA -->
<section id="final-cta" aria-labelledby="cta-h2">
  <div class="cta-ridges" aria-hidden="true">
    <svg viewBox="0 0 1440 200" xmlns="http://www.w3.org/2000/svg" style="width:100%;display:block">
      <path d="M0,200 L0,140 C100,100 160,120 250,110 C340,100 400,80 520,75 C640,70 700,95 820,90 C940,85 1020,60 1140,65 C1260,70 1360,100 1440,95 L1440,200 Z"
        fill="rgba(244,239,230,0.04)"/>
      <path d="M0,200 L0,160 C120,130 200,150 320,145 C440,140 500,115 640,108 C780,101 860,125 980,122 C1100,119 1200,100 1320,105 C1380,108 1420,120 1440,118 L1440,200 Z"
        fill="rgba(244,239,230,0.02)"/>
    </svg>
  </div>
  <div class="cta-inner">
    <p class="section-eyebrow reveal" style="justify-content:center;color:rgba(163,155,141,0.6)">Ready when you are</p>
    <h2 class="cta-h2 reveal reveal-delay-1" id="cta-h2">
      Your Nepal is<br>one plan <em>away.</em>
    </h2>
    <p class="cta-sub reveal reveal-delay-2">Free to plan. No spreadsheets. No guesswork.</p>
    <div class="cta-btns reveal reveal-delay-3">
      <?php if ($userLoggedIn): ?>
        <a href="user/generator.php" class="btn-primary" style="font-size:1rem;padding:0.9rem 2.2rem">
          Create Itinerary
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </a>
        <a href="user/dashboard.php" class="btn-outline">Go to Dashboard</a>
      <?php else: ?>
        <a href="user/index.php?tab=register" class="btn-primary" style="font-size:1rem;padding:0.9rem 2.2rem">
          Get Started
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </a>
        <a href="user/index.php?tab=login" class="btn-outline">Sign In</a>
      <?php endif; ?>
    </div>
  </div>
</section>


<!-- FOOTER -->
<footer id="footer" role="contentinfo">
  <div class="footer-deva" aria-hidden="true">यात्रा पथ</div>
  <div class="footer-inner">
    <div class="footer-brand">
      <a href="homepage.php" aria-label="YatraPath home">
        <img src="logo.svg" alt="YatraPath"
          onerror="this.outerHTML='<span style=\'font-family:var(--font-display);font-size:1.4rem;font-weight:300;font-style:italic;color:rgba(244,239,230,0.6)\'>YatraPath</span>'">
      </a>
      <p class="footer-slogan">"Let the path find you."</p>
    </div>
    
    <div class="footer-col">
      <h4>Explore</h4>
      <ul>
        <li><a href="#destinations">All destinations</a></li>
        <li><a href="#trending">Trending itineraries</a></li>
        <li><a href="#interests">Travel styles</a></li>
        <li><a href="#how">How it works</a></li>
      </ul>
    </div>
    
    <div class="footer-col">
      <h4>Traveler Portal</h4>
      <ul>
        <?php if ($userLoggedIn): ?>
          <li><a href="user/dashboard.php">Dashboard</a></li>
          <li><a href="user/generator.php">Create Itinerary</a></li>
          <li><a href="user/itineraries.php">My Itineraries</a></li>
          <li><a href="api/auth/logout.php">Sign Out</a></li>
        <?php else: ?>
          <li><a href="user/index.php?tab=register">Sign up free</a></li>
          <li><a href="user/index.php?tab=login">Log in</a></li>
          <li><a href="user/generator.php">Itinerary Wizard</a></li>
        <?php endif; ?>
      </ul>
    </div>
    
    <div class="footer-col">
      <h4>Administration</h4>
      <ul>
        <li><a href="admin/index.php">Admin Portal</a></li>
        <li><a href="admin/dashboard.php">Admin Analytics</a></li>
        <li><a href="#engine">Optimization Engine</a></li>
      </ul>
    </div>
  </div>
  
  <div class="footer-bottom">
    <p class="footer-credit">Developed as a 6th Semester BCA Project · Tribhuvan University</p>
    <p class="footer-copy">© <?= date('Y') ?> YatraPath. All rights reserved.</p>
  </div>
</footer>

</main><!-- /main -->

<!-- sr-only utility -->
<style>.sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}</style>

<!-- ═══════════════════════════════════════════════
     JAVASCRIPT
═══════════════════════════════════════════════ -->
<script>
'use strict';

// Pass PHP-rendered dynamic database records to Client
window.PHP_STATS        = <?= json_encode($stats) ?>;
window.PHP_DESTINATIONS = <?= json_encode($dbDestinations) ?>;
window.PHP_REVIEWS      = <?= json_encode($dbReviews) ?>;

/* ── Reduced motion ── */
const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)');

/* ── Scroll progress bar ── */
const progressBar = document.getElementById('scroll-progress');
function updateProgress() {
  const max = document.documentElement.scrollHeight - window.innerHeight;
  progressBar.style.transform = `scaleX(${max > 0 ? window.scrollY / max : 0})`;
}
window.addEventListener('scroll', updateProgress, { passive: true });

/* ─────────────────────────────────────────────
   INTRO OVERLAY (FLIP LOGO TRANSITION)
───────────────────────────────────────────── */
(function initIntro() {
  const intro     = document.getElementById('intro');
  const logoWrap  = document.getElementById('intro-logo-wrap');
  const navLogo   = document.getElementById('nav-logo');

  if (!intro) return;

  // Skip if seen this session
  if (sessionStorage.getItem('yp_intro_seen') || prefersReduced.matches) {
    intro.style.display = 'none';
    navLogo.style.visibility = 'visible';
    document.body.classList.remove('intro-active');
    triggerHeroEnter();
    return;
  }

  const REVEAL_START   = 100;
  const TRAVEL_START   = 900;
  const BACKDROP_START = 950;
  const TOTAL          = 1800;

  let skipped = false;

  function skipIntro() {
    if (skipped) return;
    skipped = true;
    intro.style.transition = 'opacity 0.25s';
    intro.style.opacity = '0';
    setTimeout(finishIntro, 260);
  }

  function finishIntro() {
    intro.remove();
    navLogo.style.visibility = 'visible';
    document.body.classList.remove('intro-active');
    sessionStorage.setItem('yp_intro_seen', '1');
    triggerHeroEnter();
  }

  intro.addEventListener('click', skipIntro);
  document.addEventListener('keydown', skipIntro, { once: true });

  // Phase 1: logo reveal
  logoWrap.style.opacity    = '0';
  logoWrap.style.transform  = 'scale(0.92)';
  logoWrap.style.filter     = 'blur(6px)';
  logoWrap.style.willChange = 'transform, opacity, filter';

  setTimeout(() => {
    logoWrap.style.transition = `opacity 550ms cubic-bezier(0.22,1,0.36,1),
                                  transform 550ms cubic-bezier(0.22,1,0.36,1),
                                  filter 550ms cubic-bezier(0.22,1,0.36,1)`;
    logoWrap.style.opacity    = '1';
    logoWrap.style.transform  = 'scale(1)';
    logoWrap.style.filter     = 'blur(0px)';
  }, REVEAL_START);

  // Phase 2: logo travels to navbar
  setTimeout(() => {
    if (skipped) return;

    const introRect = logoWrap.getBoundingClientRect();
    navLogo.style.visibility = 'hidden';
    const navRect   = navLogo.getBoundingClientRect();

    if (!navRect.width) { finishIntro(); return; }

    const scaleTarget = navRect.width / introRect.width;
    const tx = navRect.left + navRect.width / 2 - (introRect.left + introRect.width / 2);
    const ty = navRect.top  + navRect.height / 2 - (introRect.top  + introRect.height / 2);

    logoWrap.style.transition = `transform 650ms cubic-bezier(0.76,0,0.24,1),
                                  filter 650ms cubic-bezier(0.76,0,0.24,1)`;
    logoWrap.style.transform  = `translate(${tx}px, ${ty}px) scale(${scaleTarget})`;
    logoWrap.style.filter     = 'blur(1.5px)';

    // Backdrop fade
    setTimeout(() => {
      intro.style.transition = 'opacity 550ms cubic-bezier(0.22,1,0.36,1)';
      intro.style.opacity    = '0';
    }, BACKDROP_START - TRAVEL_START);

  }, TRAVEL_START);

  // Phase 3: clean up
  setTimeout(() => {
    if (skipped) return;
    logoWrap.style.filter     = 'blur(0px)';
    logoWrap.style.willChange = 'auto';
    navLogo.style.visibility  = 'visible';
    finishIntro();
  }, TOTAL);

})();

function triggerHeroEnter() {
  const els = [
    document.getElementById('hero-eyebrow'),
    document.getElementById('hero-h1'),
    document.getElementById('hero-ticker'),
    document.getElementById('hero-sub'),
    document.getElementById('quick-search'),
    document.getElementById('scroll-cue'),
  ];
  els.forEach((el, i) => {
    if (!el) return;
    setTimeout(() => el && el.classList.add('visible'), i * 80);
  });
}

/* ─────────────────────────────────────────────
   HERO SLIDESHOW + ROTATING TICKER
───────────────────────────────────────────── */
const slides = [
  { slide:'slide-0', caption:'Annapurna · Gandaki · 28.5937° N',   tick:'Annapurna dawns'     },
  { slide:'slide-1', caption:'Kathmandu · Bagmati · 27.7172° N',   tick:'Kathmandu alleys'    },
  { slide:'slide-2', caption:'Chitwan · Narayani · 27.5291° N',    tick:'Chitwan mornings'    },
  { slide:'slide-3', caption:'Phewa Lake · Gandaki · 28.2096° N',  tick:'Phewa Lake evenings' },
];
let currentSlide = 0;
const SLIDE_DURATION = 4800;

function nextSlide() {
  const prev = currentSlide;
  currentSlide = (currentSlide + 1) % slides.length;

  document.getElementById(slides[prev].slide).classList.remove('active');
  document.getElementById(slides[prev].slide).classList.add('exiting');
  setTimeout(() => document.getElementById(slides[prev].slide).classList.remove('exiting'), 1400);
  document.getElementById(slides[currentSlide].slide).classList.add('active');

  const cap = document.getElementById('hero-caption-text');
  if (cap) cap.textContent = slides[currentSlide].caption;

  const words = document.querySelectorAll('.ticker-word');
  words.forEach(w => { w.className = 'ticker-word next'; });
  if (words[currentSlide]) words[currentSlide].className = 'ticker-word current';

  resetProgress();
}

function resetProgress() {
  const bar = document.getElementById('hero-progress-bar');
  if (!bar) return;
  bar.style.transition = 'none';
  bar.style.transform = 'scaleX(0)';
  setTimeout(() => {
    bar.style.transition = `transform ${SLIDE_DURATION}ms linear`;
    bar.style.transform  = 'scaleX(1)';
  }, 30);
}

if (!prefersReduced.matches) {
  resetProgress();
  setInterval(nextSlide, SLIDE_DURATION);
}

/* ─────────────────────────────────────────────
   SEASON WIDGET (KATHMANDU CLOCK)
───────────────────────────────────────────── */
function updateSeasonWidget() {
  const now = new Date(new Date().toLocaleString('en-US', { timeZone: 'Asia/Kathmandu' }));
  const h = now.getHours(), m = now.getMinutes();
  const timeEl = document.getElementById('ktm-time');
  if (timeEl) timeEl.textContent = `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}`;

  const month = now.getMonth() + 1;
  let season, tip;
  if (month >= 3 && month <= 5)       { season = 'Spring';  tip = 'Rhododendrons in bloom'; }
  else if (month >= 6 && month <= 9)  { season = 'Monsoon'; tip = 'Lush green valleys'; }
  else if (month >= 10 && month <= 11){ season = 'Autumn';  tip = 'Peak trekking weather'; }
  else                                { season = 'Winter';  tip = 'Crisp mountain panoramas'; }

  const nameEl = document.getElementById('season-name');
  const tipEl  = document.getElementById('season-tip');
  if (nameEl) nameEl.textContent = season;
  if (tipEl)  tipEl.textContent  = tip;
}
updateSeasonWidget();
setInterval(updateSeasonWidget, 30000);

/* ─────────────────────────────────────────────
   NAVBAR BEHAVIOUR
───────────────────────────────────────────── */
const navbar = document.getElementById('navbar');
let lastScroll = 0;
window.addEventListener('scroll', () => {
  const y = window.scrollY;
  if (y > 40) navbar.classList.add('scrolled'); else navbar.classList.remove('scrolled');
  if (y > lastScroll && y > 200) navbar.classList.add('hidden');
  else navbar.classList.remove('hidden');
  lastScroll = y;
}, { passive: true });

// Mobile Hamburger
const ham = document.getElementById('hamburger');
const mobileMenu = document.getElementById('mobile-menu');
const menuClose  = document.getElementById('menu-close');

function openMenu() {
  mobileMenu.classList.add('open');
  mobileMenu.setAttribute('aria-hidden','false');
  ham.setAttribute('aria-expanded','true');
  document.body.style.overflow = 'hidden';
}
function closeMenu() {
  mobileMenu.classList.remove('open');
  mobileMenu.setAttribute('aria-hidden','true');
  ham.setAttribute('aria-expanded','false');
  document.body.style.overflow = '';
}

ham.addEventListener('click', openMenu);
menuClose.addEventListener('click', closeMenu);
mobileMenu.querySelectorAll('a').forEach(a => a.addEventListener('click', closeMenu));
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeMenu(); });

/* ─────────────────────────────────────────────
   QUICK SEARCH INTERACTION
───────────────────────────────────────────── */
let qsDays = 5;
const daysVal = document.getElementById('days-val');
document.getElementById('days-minus').addEventListener('click', () => {
  qsDays = Math.max(1, qsDays - 1);
  daysVal.textContent = qsDays;
  recalcBudget();
});
document.getElementById('days-plus').addEventListener('click', () => {
  qsDays = Math.min(30, qsDays + 1);
  daysVal.textContent = qsDays;
  recalcBudget();
});

document.getElementById('qs-budget').addEventListener('input', recalcBudget);

function getBudgetTier(perDay) {
  if (perDay < 3000)  return { label: 'Budget',    bg: 'rgba(143,165,138,0.18)', color: '#8FA58A' };
  if (perDay <= 10000) return { label: 'Mid-range', bg: 'rgba(200,151,58,0.18)',  color: 'var(--marigold)' };
  return               { label: 'Luxury',   bg: 'rgba(180,83,58,0.18)',   color: 'var(--terracotta)' };
}

function recalcBudget() {
  const total = parseInt(document.getElementById('qs-budget').value) || 0;
  if (!total || !qsDays) {
    document.getElementById('qs-perday').textContent = '';
    document.getElementById('qs-tier').textContent = '';
    return;
  }
  const perDay = Math.round(total / qsDays);
  const tier   = getBudgetTier(perDay);
  document.getElementById('qs-perday').textContent = `NPR ${perDay.toLocaleString()}/day`;
  const tierEl = document.getElementById('qs-tier');
  tierEl.textContent = tier.label;
  tierEl.style.background = tier.bg;
  tierEl.style.color      = tier.color;
}
recalcBudget();

document.getElementById('qs-submit').addEventListener('click', () => {
  const dest   = document.getElementById('qs-dest').value.trim();
  const budget = document.getElementById('qs-budget').value.trim();
  
  const prefill = { destination: dest, days: qsDays, budget: budget || '25000' };
  sessionStorage.setItem('yp_prefill', JSON.stringify(prefill));

  let redirectUrl = 'user/generator.php?days=' + encodeURIComponent(qsDays);
  if (dest) redirectUrl += '&dest=' + encodeURIComponent(dest);
  if (budget) redirectUrl += '&budget=' + encodeURIComponent(budget);

  window.location.href = redirectUrl;
});

/* ─────────────────────────────────────────────
   ENGINE DEMO (ALGORITHM PREVIEW)
───────────────────────────────────────────── */
const demoData = {
  spring: {
    adventure: {
      title:'Day 1 · Kathmandu Valley',
      slots:[
        { name:'Shivapuri Forest Trail', desc:'Morning hike under blooming rhododendrons with valley views.', cost:'NPR 500' },
        { name:'Nagarkot Ridge Walk', desc:'Afternoon panoramic ridge hike towards eastern Himalaya.', cost:'Free' },
        { name:'Thamel Rooftop Dining', desc:'Evening local Newari tasting menu and herbal tea.', cost:'NPR 900' }
      ],
      spend:'NPR 1,400', pct:'20%', caption:'Spring detected — rhododendrons blooming, clear mornings.'
    },
    cultural: {
      title:'Day 1 · Bhaktapur & Patan',
      slots:[
        { name:'Bhaktapur Durbar Square', desc:'Dawn stroll through ancient pagoda temples and pottery alleys.', cost:'NPR 500' },
        { name:'Patan Museum & Golden Temple', desc:'Masterpieces of bronze casting and Malla court art.', cost:'NPR 400' },
        { name:'Traditional Newari Feast', desc:'Samay Baji and Choila platter by the courtyard.', cost:'NPR 750' }
      ],
      spend:'NPR 1,650', pct:'24%', caption:'Spring detected — pleasant weather for temple walking.'
    }
  },
  monsoon: {
    cultural: {
      title:'Day 1 · Patan Art & Heritage',
      slots:[
        { name:'Patan Royal Palace Interiors', desc:'Sheltered courtyards, rain-washed brick architecture.', cost:'NPR 500' },
        { name:'Traditional Thanka Gallery', desc:'Painting masterclass with master artisans.', cost:'NPR 600' },
        { name:'Aroma Coffee & Momos', desc:'Evening quiet contemplation listening to courtyard rainfall.', cost:'NPR 400' }
      ],
      spend:'NPR 1,500', pct:'21%', caption:'Monsoon detected — trekking routes filtered, cultural spots prioritized.'
    },
    nature: {
      title:'Day 1 · Chitwan National Park',
      slots:[
        { name:'Canoeing on Rapti River', desc:'Morning peaceful drift spotting gharial crocodiles in mist.', cost:'NPR 800' },
        { name:'Elephant Breeding Center', desc:'Afternoon conservation educational walk.', cost:'NPR 300' },
        { name:'Tharu Cultural Dance', desc:'Evening traditional stick dance by local villagers.', cost:'NPR 500' }
      ],
      spend:'NPR 1,600', pct:'23%', caption:'Monsoon detected — Terai lowlands lush, safe from mountain slides.'
    }
  },
  autumn: {
    adventure: {
      title:'Day 1 · Pokhara & Sarangkot',
      slots:[
        { name:'Sarangkot Sunrise Flight', desc:'Dawn viewpoint over Annapurna range and Dhaulagiri.', cost:'Free' },
        { name:'Phewa Lake Kayaking', desc:'Afternoon paddle on calm waters beneath mountain reflections.', cost:'NPR 600' },
        { name:'Lakeside Woodfire Pizza', desc:'Evening dinner with live acoustic folk music.', cost:'NPR 1,100' }
      ],
      spend:'NPR 1,700', pct:'24%', caption:'Autumn peak detected — crystal clear skies, best for trekking.'
    },
    cultural: {
      title:'Day 1 · Kathmandu Sacred Sites',
      slots:[
        { name:'Boudhanath Stupa Circumambulation', desc:'Morning clockwise prayer walk with Tibetan pilgrims.', cost:'NPR 200' },
        { name:'Pashupatinath Temple', desc:'Afternoon exploration of Nepal\'s holiest Shiva shrine.', cost:'NPR 1,000' },
        { name:'Bagmati River Evening Aarti', desc:'Twilight incense and oil lamp musical ritual.', cost:'Free' }
      ],
      spend:'NPR 1,200', pct:'17%', caption:'Autumn peak detected — festive season with clear skies.'
    }
  },
  winter: {
    cultural: {
      title:'Day 1 · Kathmandu Valley Sun',
      slots:[
        { name:'Swayambhunath Monkey Temple', desc:'Warm morning climb to golden spire overlooking the bowl.', cost:'NPR 200' },
        { name:'Kathmandu Durbar Square', desc:'Afternoon exploration of Kumari Ghar and ancient courtyards.', cost:'NPR 500' },
        { name:'Warm Thukpa & Masala Tea', desc:'Evening hearty Himalayan noodle soup in old alleys.', cost:'NPR 500' }
      ],
      spend:'NPR 1,200', pct:'17%', caption:'Winter detected — warm sunny valley days, crisp snow-capped views.'
    }
  }
};

let currentSeason = 'autumn';
let currentInt    = 'cultural';

function updateEngineDemo() {
  const seasonData = demoData[currentSeason] || demoData.autumn;
  const plan = seasonData[currentInt] || Object.values(seasonData)[0];

  document.getElementById('demo-title').textContent = plan.title;
  document.getElementById('engine-caption').textContent = plan.caption;

  plan.slots.forEach((s, idx) => {
    const nEl = document.getElementById(`slot-${idx}-name`);
    const dEl = document.getElementById(`slot-${idx}-desc`);
    const cEl = document.getElementById(`slot-${idx}-cost`);
    if (nEl) nEl.textContent = s.name;
    if (dEl) dEl.textContent = s.desc;
    if (cEl) cEl.textContent = s.cost;
  });

  const budget = parseInt(document.getElementById('budget-slider').value) || 35000;
  const perDay = Math.round(budget / 5);
  document.getElementById('demo-spend-label').textContent = `${plan.spend} / ${perDay.toLocaleString()}`;
  document.getElementById('demo-bar').style.width = plan.pct;

  // Animate algo stepper
  document.querySelectorAll('.algo-step').forEach((el, i) => {
    setTimeout(() => {
      el.classList.add('active');
      setTimeout(() => el.classList.remove('active'), 600);
    }, i * 90);
  });
}

document.querySelectorAll('.season-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.season-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    currentSeason = tab.dataset.season;
    updateEngineDemo();
  });
});

document.querySelectorAll('.interest-chip').forEach(chip => {
  chip.addEventListener('click', () => {
    document.querySelectorAll('.interest-chip').forEach(c => c.classList.remove('selected'));
    chip.classList.add('selected');
    currentInt = chip.dataset.int;
    updateEngineDemo();
  });
});

const bSlider = document.getElementById('budget-slider');
bSlider.addEventListener('input', () => {
  const val = parseInt(bSlider.value);
  document.getElementById('budget-val').textContent = `NPR ${val.toLocaleString()}`;
  document.getElementById('budget-display-label').textContent = `NPR ${val.toLocaleString()}`;
  document.getElementById('demo-budget').textContent = `Budget: NPR ${val.toLocaleString()} · 5 days`;
  const tier = getBudgetTier(Math.round(val / 5));
  document.getElementById('budget-tier').textContent = tier.label;
  updateEngineDemo();
});

updateEngineDemo();

/* ─────────────────────────────────────────────
   FEATURED DESTINATIONS RENDERING
───────────────────────────────────────────── */
const fallbackDests = [
  { id:1, name:'Pokhara',    region:'Gandaki',   avg_cost_per_day:3500, avg_rating:4.9, suitable_seasons:'Autumn,Spring,Winter', color:'#1B3B4B' },
  { id:2, name:'Kathmandu',  region:'Bagmati',   avg_cost_per_day:2800, avg_rating:4.8, suitable_seasons:'Autumn,Spring',        color:'#2B3A45' },
  { id:3, name:'Chitwan',    region:'Narayani',  avg_cost_per_day:4200, avg_rating:4.7, suitable_seasons:'Spring,Autumn,Winter', color:'#1a2a38' },
  { id:4, name:'Bhaktapur',  region:'Bagmati',   avg_cost_per_day:2000, avg_rating:4.7, suitable_seasons:'Autumn,Spring',        color:'#1E3024' },
  { id:5, name:'Nagarkot',   region:'Bagmati',   avg_cost_per_day:2400, avg_rating:4.6, suitable_seasons:'Autumn,Winter,Spring', color:'#25383A' },
  { id:6, name:'Lumbini',    region:'Rupandehi', avg_cost_per_day:1800, avg_rating:4.5, suitable_seasons:'Winter,Spring',       color:'#2a3520' },
];

function starsHTML(rating) {
  return Array.from({length:5}, (_, i) =>
    `<div class="star ${i < Math.round(rating) ? 'filled' : ''}"></div>`).join('');
}

function renderDests(data) {
  const grid = document.getElementById('dest-grid');
  if (!grid) return;
  grid.innerHTML = data.slice(0, 6).map((d, i) => {
    const seasons = typeof d.suitable_seasons === 'string' 
      ? d.suitable_seasons.split(',').map(s=>s.trim()) 
      : (d.seasons || ['Autumn', 'Spring']);
    const cost = d.avg_cost_per_day || d.cost_per_day || 3000;
    const rating = d.avg_rating || d.rating || 4.8;
    const color = d.color || '#1B3B4B';
    const bgStyle = d.image_url 
      ? `background-image:url('${d.image_url}');background-size:cover;background-position:center;`
      : `background:${color};background-image:linear-gradient(135deg,${color} 0%,${color}aa 100%);`;

    return `
    <a href="user/generator.php?destination=${d.id}" class="dest-card reveal ${i===0?'reveal-delay-0':'reveal-delay-'+Math.min(i,4)}"
      aria-label="${d.name}, ${d.region}">
      <div class="dest-card-img-wrap">
        <div class="dest-card-img" style="${bgStyle}height:100%;width:100%"></div>
      </div>
      <div class="dest-card-body">
        <div class="dest-name">${d.name}</div>
        <div class="dest-region">${d.region} Region</div>
        <div class="dest-meta">
          <span class="dest-cost">NPR ${Number(cost).toLocaleString()}/day</span>
          <div class="dest-stars" aria-label="${rating} out of 5 stars">${starsHTML(rating)}</div>
        </div>
        <div style="display:flex;gap:0.4rem;margin-top:0.75rem;flex-wrap:wrap">
          ${seasons.map(s=>`<span style="font-size:0.65rem;padding:0.18rem 0.55rem;border-radius:100px;background:rgba(200,151,58,0.14);color:var(--marigold);font-weight:600">${s}</span>`).join('')}
        </div>
      </div>
      <div class="dest-hover-cta" aria-hidden="true">Plan Trip <span>→</span></div>
    </a>
  `}).join('');

  // 3D card tilt
  if (!prefersReduced.matches) {
    document.querySelectorAll('.dest-card').forEach(card => {
      card.addEventListener('mousemove', e => {
        const r = card.getBoundingClientRect();
        const x = (e.clientX - r.left) / r.width  - 0.5;
        const y = (e.clientY - r.top)  / r.height - 0.5;
        card.style.transform = `perspective(800px) rotateY(${x*6}deg) rotateX(${-y*6}deg) translateY(-6px)`;
      });
      card.addEventListener('mouseleave', () => {
        card.style.transition = 'transform 0.5s var(--ease-out)';
        card.style.transform  = '';
        setTimeout(() => card.style.transition = '', 500);
      });
    });
  }
}

// Initial render with DB data or fallback
const initialDests = window.PHP_DESTINATIONS && window.PHP_DESTINATIONS.length ? window.PHP_DESTINATIONS : fallbackDests;
renderDests(initialDests);

// Client-side API fetch fallback
fetch('api/destinations/index.php')
  .then(r => r.json())
  .then(res => {
    const list = res.data || res;
    if (Array.isArray(list) && list.length) renderDests(list);
  })
  .catch(() => {});

/* ─────────────────────────────────────────────
   INTEREST TILES (VIBE SELECTION)
───────────────────────────────────────────── */
const interestData = [
  { name:'Adventure & Trekking', sub:'Annapurna · Everest · Langtang', icon:'M12 2L2 19h20L12 2z' },
  { name:'Cultural & Heritage',  sub:'Durbar Squares · Monasteries',    icon:'M3 12h18M3 6h18M3 18h18' },
  { name:'Nature & Wildlife',    sub:'Chitwan · Bardia · Koshi Tappu',  icon:'M12 22s-8-4.5-8-11.8A8 8 0 0 1 12 2a8 8 0 0 1 8 8.2c0 7.3-8 11.8-8 11.8z' },
  { name:'Food & Local Cuisine', sub:'Momos · Thakali · Newari',        icon:'M18 8h1a4 4 0 0 1 0 8h-1M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z' },
  { name:'Wellness & Spiritual', sub:'Meditation · Yoga · Lumbini',     icon:'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm0 4v8l4 2' },
  { name:'Photography',          sub:'Golden hour · Ancient temples',   icon:'M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z M12 17a4 4 0 1 0 0-8 4 4 0 0 0 0 8z' },
];
const selectedInterests = new Set();

function renderInterests() {
  const container = document.getElementById('interest-tiles');
  if (!container) return;
  container.innerHTML = interestData.map((d, i) => `
    <div class="interest-tile" tabindex="0" role="checkbox" aria-checked="false" data-i="${i}">
      <div class="interest-icon-box">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="${d.icon}"/>
        </svg>
      </div>
      <div class="interest-tile-title">${d.name}</div>
      <div class="interest-tile-sub">${d.sub}</div>
      <div class="interest-check">✓</div>
    </div>
  `).join('');

  container.querySelectorAll('.interest-tile').forEach(tile => {
    function toggle() {
      const idx = parseInt(tile.dataset.i);
      if (selectedInterests.has(idx)) {
        selectedInterests.delete(idx);
        tile.classList.remove('selected');
        tile.setAttribute('aria-checked', 'false');
      } else {
        selectedInterests.add(idx);
        tile.classList.add('selected');
        tile.setAttribute('aria-checked', 'true');
      }
      const btn = document.getElementById('interests-cta-btn');
      if (selectedInterests.size > 0) {
        btn.removeAttribute('disabled');
        btn.style.opacity = '1';
        btn.style.cursor  = 'pointer';
      } else {
        btn.setAttribute('disabled', '');
        btn.style.opacity = '0.4';
        btn.style.cursor  = 'default';
      }
    }
    tile.addEventListener('click', toggle);
    tile.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); } });
  });
}
renderInterests();

document.getElementById('interests-cta-btn').addEventListener('click', () => {
  const chosen = Array.from(selectedInterests).map(i => interestData[i].name).join(',');
  sessionStorage.setItem('yp_interests', chosen);
  window.location.href = 'user/generator.php?interests=' + encodeURIComponent(chosen);
});

/* ─────────────────────────────────────────────
   TRENDING ITINERARIES (TICKETS)
───────────────────────────────────────────── */
const fallbackTickets = [
  { dest:'Kathmandu → Pokhara → Chitwan', days:7, tier:'Mid-range', saves:248, cities:['Kathmandu','Pokhara','Chitwan'] },
  { dest:'Pokhara → Annapurna BC',        days:12,tier:'Budget',    saves:197, cities:['Pokhara','Birethanti','ABC']    },
  { dest:'Kathmandu → Bhaktapur → Nagarkot', days:4, tier:'Comfort', saves:184, cities:['Kathmandu','Bhaktapur','Nagarkot'] },
  { dest:'Lumbini → Chitwan',             days:5, tier:'Budget',    saves:162, cities:['Lumbini','Sauraha']            },
  { dest:'Kathmandu Valley Loop',         days:3, tier:'Mid-range', saves:141, cities:['Patan','Bhaktapur','Nagarkot'] },
  { dest:'Pokhara → Mustang',             days:10,tier:'Luxury',    saves:120, cities:['Pokhara','Jomsom','Lo Manthang']},
];

function renderTickets(data) {
  const row = document.getElementById('tickets-row');
  if (!row) return;
  row.innerHTML = data.slice(0, 3).map(t => {
    const cities = t.cities || t.dest.split('→').map(s=>s.trim());
    return `
    <div class="ticket reveal">
      <div class="ticket-header">
        <span class="ticket-dest">${cities[0]} → ${cities[cities.length-1]}</span>
        <span class="ticket-days">${t.days} days</span>
      </div>
      <div class="ticket-perf" aria-hidden="true"></div>
      <div class="ticket-body">
        <div class="ticket-route">
          ${cities.map((c, i, arr) => i < arr.length-1
            ? `<span class="ticket-city">${c}</span><div class="ticket-arrow" aria-hidden="true"></div>`
            : `<span class="ticket-city">${c}</span>`).join('')}
        </div>
        <div class="ticket-meta">
          <span class="ticket-chip budget">${t.tier || 'Mid-range'}</span>
          <span class="ticket-chip">${t.days} nights</span>
        </div>
      </div>
      <div class="ticket-footer">
        <span class="ticket-saves">♥ ${t.saves || 150} saves</span>
        <a href="user/generator.php?template=${encodeURIComponent(t.dest)}&days=${t.days}" class="ticket-use">Use as template →</a>
      </div>
    </div>
  `}).join('');
}
renderTickets(fallbackTickets);

/* ─────────────────────────────────────────────
   ANIMATED STATS COUNTER
───────────────────────────────────────────── */
const statsData = window.PHP_STATS || { dest:7, act:21, plans:45, reviews:12 };

function animateCount(el, target, dur) {
  if (!el) return;
  if (prefersReduced.matches) { el.innerHTML = `${target.toLocaleString()}<span>+</span>`; return; }
  const start = performance.now();
  function step(now) {
    const pct  = Math.min((now - start) / dur, 1);
    const ease = 1 - Math.pow(1 - pct, 3);
    el.innerHTML = `${Math.round(ease * target).toLocaleString()}<span>+</span>`;
    if (pct < 1) requestAnimationFrame(step);
  }
  requestAnimationFrame(step);
}

const statsObserver = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      animateCount(document.getElementById('stat-dest'),    statsData.dest,    1200);
      animateCount(document.getElementById('stat-act'),     statsData.act,     1400);
      animateCount(document.getElementById('stat-plans'),   statsData.plans,   1600);
      animateCount(document.getElementById('stat-reviews'), statsData.reviews, 1800);
      statsObserver.disconnect();
    }
  });
}, { threshold: 0.3 });
const statsEl = document.getElementById('stats');
if (statsEl) statsObserver.observe(statsEl);

/* ─────────────────────────────────────────────
   STORIES / REVIEWS CAROUSEL
───────────────────────────────────────────── */
const fallbackReviews = [
  { initials:'SK', name:'Sushma K.', trip:'Pokhara · Oct 2024',   rating:5, text:'I gave YatraPath my budget and three destinations. What came back was better than anything I could have planned in a week of research.' },
  { initials:'RM', name:'Rahul M.',  trip:'Kathmandu · Sep 2024', rating:5, text:'The season filter alone saved us from a miserable monsoon trek. It rerouted us to Bhaktapur instead. That decision made the whole trip.' },
  { initials:'AL', name:'Anna L.',   trip:'Chitwan · Nov 2024',    rating:5, text:'Three seconds. I timed it. A seven-day plan with morning, afternoon and evening slots, all within my NPR budget. Extraordinary.' },
  { initials:'PT', name:'Priya T.',  trip:'Annapurna · Oct 2024', rating:5, text:'I travel solo and the route-optimisation gave me confidence that I wasn\'t backtracking or wasting a day between stops.' },
];

let currentReview = 0;
let reviewTimer;

function renderReviews(data) {
  const slider = document.getElementById('quote-slider');
  const nav    = document.getElementById('quote-nav');
  if (!slider || !nav) return;

  slider.innerHTML = data.map((r, i) => {
    const initials = r.initials || (r.user_name ? r.user_name.split(' ').map(n=>n[0]).join('').toUpperCase() : 'YP');
    const name = r.name || r.user_name || 'Traveler';
    const trip = r.trip || `${r.dest_name || 'Nepal'} · ${r.trip_month_year || 'Recent'}`;
    const comment = r.text || r.comment || '';

    return `
    <div class="quote-slide ${i===0?'active':''}" id="review-${i}">
      <blockquote class="quote-text">"${comment}"</blockquote>
      <div class="quote-author">
        <div class="quote-avatar" aria-hidden="true">${initials}</div>
        <div class="quote-meta">
          <div class="quote-name">${name}</div>
          <div class="quote-trip">${trip}</div>
        </div>
      </div>
    </div>
  `}).join('');

  nav.innerHTML = data.map((_, i) =>
    `<button type="button" class="quote-dot ${i===0?'active':''}" aria-label="Review ${i+1}" data-i="${i}"></button>`
  ).join('');

  nav.querySelectorAll('.quote-dot').forEach(dot => {
    dot.addEventListener('click', () => goToReview(parseInt(dot.dataset.i), data));
  });
}

function goToReview(i, data) {
  const slides = document.querySelectorAll('.quote-slide');
  const dots   = document.querySelectorAll('.quote-dot');
  if (!slides.length) return;
  slides[currentReview].classList.remove('active');
  dots[currentReview].classList.remove('active');
  currentReview = i;
  slides[currentReview].classList.add('active');
  dots[currentReview].classList.add('active');
}

function startReviewTimer(data) {
  clearInterval(reviewTimer);
  reviewTimer = setInterval(() => {
    goToReview((currentReview + 1) % data.length, data);
  }, 5200);
}

function initReviews(data) {
  renderReviews(data);
  if (!prefersReduced.matches) startReviewTimer(data);
  const slider = document.getElementById('quote-slider');
  if (slider) {
    slider.addEventListener('mouseenter', () => clearInterval(reviewTimer));
    slider.addEventListener('mouseleave', () => { if (!prefersReduced.matches) startReviewTimer(data); });
  }
}

const initialReviews = window.PHP_REVIEWS && window.PHP_REVIEWS.length ? window.PHP_REVIEWS : fallbackReviews;
initReviews(initialReviews);

/* ─────────────────────────────────────────────
   MAP ROUTE ANIMATION
───────────────────────────────────────────── */
const mapObserver = new IntersectionObserver(entries => {
  if (!entries[0].isIntersecting || prefersReduced.matches) return;
  const path = document.getElementById('route-path');
  if (path) {
    path.style.transition = 'stroke-dashoffset 1.4s cubic-bezier(0.22,1,0.36,1) 0.3s';
    path.style.strokeDashoffset = '0';
  }
  ['pin-ktm','pin-pkr','pin-cht'].forEach((id, i) => {
    const pin = document.getElementById(id);
    if (!pin) return;
    setTimeout(() => {
      pin.querySelectorAll('circle').forEach(c => {
        c.style.transition = 'opacity 0.5s';
        c.style.opacity = '1';
      });
    }, 400 + i * 300);
  });
  mapObserver.disconnect();
}, { threshold: 0.3 });
const mapSection = document.getElementById('map-teaser');
if (mapSection) mapObserver.observe(mapSection);

/* ─────────────────────────────────────────────
   INTERSECTION OBSERVER — REVEAL ON SCROLL
───────────────────────────────────────────── */
const revealObserver = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      e.target.classList.add('visible');
      revealObserver.unobserve(e.target);
    }
  });
}, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));

</script>
</body>
</html>
