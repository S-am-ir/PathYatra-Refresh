import React from 'react';
import { Link } from 'react-router-dom';
import { ArrowRight } from 'lucide-react';
import './home.css';

export default function NotFound() {
  return <div className="travel-page shell travel-empty" style={{ minHeight: '65vh', display: 'flex', flexDirection: 'column', justifyContent: 'center', border: 0, background: 'transparent' }}>
    <span className="eyebrow">404 / Lost the trail</span><h1 style={{ fontSize: 'clamp(3rem,6vw,6rem)', margin: '1rem 0' }}>This path ends here.</h1>
    <p>The page you were looking for has moved or does not exist.</p>
    <Link className="button button--ink" to="/">Return home <ArrowRight size={17} /></Link>
  </div>;
}
