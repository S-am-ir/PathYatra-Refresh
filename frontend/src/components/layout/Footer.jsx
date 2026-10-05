import React from 'react';
import { Link } from 'react-router-dom';
import './site-layout.css';

export default function Footer() {
  return (
    <footer className="site-footer">
      <div className="shell site-footer__grid">
        <div><Link className="site-footer__brand" to="/">PathYatra<span>पथयात्रा</span></Link><p>A thoughtful starting point for journeys through Nepal.</p></div>
        <div><span className="site-footer__label">Explore</span><Link to="/destinations">Destinations</Link><Link to="/generator">Plan a trip</Link></div>
        <div><span className="site-footer__label">Your journey</span><Link to="/my-itineraries">Saved trips</Link><Link to="/login">Sign in</Link></div>
      </div>
      <div className="shell site-footer__bottom"><span>© {new Date().getFullYear()} PathYatra</span><span>Made for journeys through Nepal · Estimates are for planning</span></div>
    </footer>
  );
}
