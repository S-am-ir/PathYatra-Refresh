import React, { useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { ArrowRight } from 'lucide-react';
import { useAuth } from '../../context/AuthContext';
import './auth.css';

export default function RegisterPage() {
  const { register } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const [form, setForm] = useState({ name: '', email: '', password: '', confirm_password: '' });
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const update = (field) => (event) => setForm((current) => ({ ...current, [field]: event.target.value }));
  const submit = async (event) => {
    event.preventDefault(); setError('');
    if (form.password !== form.confirm_password) { setError('The passwords do not match.'); return; }
    setLoading(true);
    try {
      await register(form);
      const from = location.state?.from;
      navigate(from?.pathname || '/dashboard', { replace: true, state: from?.state });
    } catch (err) { setError(err.message || 'Could not create your account.'); }
    finally { setLoading(false); }
  };
  return <div className="auth-page"><div className="auth-image" aria-hidden="true" /><div className="auth-panel"><div className="auth-panel__inner">
    <span className="eyebrow">A place for your plans</span><h1>Begin here.</h1><p>Create an account to keep your journeys together.</p>
    {error && <div className="travel-error" role="alert">{error}</div>}
    <form className="auth-form" onSubmit={submit}>
      <label>Your name<input type="text" autoComplete="name" value={form.name} onChange={update('name')} required /></label>
      <label>Email address<input type="email" autoComplete="email" value={form.email} onChange={update('email')} required /></label>
      <label>Password · at least 8 characters<input type="password" autoComplete="new-password" minLength={8} value={form.password} onChange={update('password')} required /></label>
      <label>Confirm password<input type="password" autoComplete="new-password" minLength={8} value={form.confirm_password} onChange={update('confirm_password')} required /></label>
      <button type="submit" disabled={loading} className="button button--ink">{loading ? 'Creating account…' : 'Create account'} <ArrowRight size={17} /></button>
    </form>
    <div className="auth-switch">Already have an account? <Link to="/login" state={location.state}>Sign in</Link></div>
  </div></div></div>;
}
