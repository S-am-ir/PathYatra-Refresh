import React, { useEffect, useState } from 'react';
import { Link, NavLink, useNavigate } from 'react-router-dom';
import { ArrowUpRight, Menu, X } from 'lucide-react';
import { useAuth } from '../../context/AuthContext';
import './site-layout.css';

export default function Navbar() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [menuOpen, setMenuOpen] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    if (!menuOpen) return;
    const onKey = (event) => { if (event.key === 'Escape') setMenuOpen(false); };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [menuOpen]);

  const signOut = async () => {
    setMenuOpen(false);
    try { await logout(); navigate('/'); setError(''); } catch (err) { setError(err.message); }
  };
  const closeMenu = () => setMenuOpen(false);

  return (
    <header className="site-header">
      <div className="site-header__inner shell">
        <Link className="site-brand" to="/" onClick={closeMenu} aria-label="PathYatra home">
          <img className="site-brand__mark" src="/brand-mark.svg" alt="" width="35" height="35" />
          <span>PathYatra<small>Journeys through Nepal</small></span>
        </Link>
        <nav className={menuOpen ? 'site-nav site-nav--open' : 'site-nav'} aria-label="Main navigation">
          <NavLink to="/destinations" onClick={closeMenu}>Explore</NavLink>
          <NavLink to="/generator" onClick={closeMenu}>Plan a trip</NavLink>
          {user && user.role !== 'admin' && <NavLink to="/my-itineraries" onClick={closeMenu}>My trips</NavLink>}
          {user?.role === 'admin' && <NavLink to="/admin" onClick={closeMenu}>Admin</NavLink>}
          <div className="site-nav__mobile-actions">
            {user ? <button type="button" onClick={signOut}>Sign out</button> : <Link to="/login" onClick={closeMenu}>Sign in</Link>}
          </div>
        </nav>
        <div className="site-header__actions">
          {user ? <button type="button" className="site-header__sign" onClick={signOut}>Sign out</button> : <Link className="site-header__sign" to="/login">Sign in</Link>}
          <Link className="site-header__cta" to={user?.role === 'admin' ? '/admin' : '/generator'}>{user?.role === 'admin' ? 'Dashboard' : 'Start planning'} <ArrowUpRight size={16} /></Link>
        </div>
        <button type="button" className="site-header__toggle" aria-label={menuOpen ? 'Close menu' : 'Open menu'} aria-expanded={menuOpen} onClick={() => setMenuOpen(!menuOpen)}>
          {menuOpen ? <X size={25} /> : <Menu size={25} />}
        </button>
      </div>
      {error && <p className="travel-error" role="alert">{error}</p>}
    </header>
  );
}
