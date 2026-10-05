import React, { useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { ArrowRight } from 'lucide-react';
import { useAuth } from '../../context/AuthContext';
import './auth.css';

export default function LoginPage() {
  const { login } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const submit = async (event) => {
    event.preventDefault(); setError(''); setLoading(true);
    try {
      const user = await login(email, password);
      const from = location.state?.from;
      navigate(from?.pathname || (user.role === 'admin' ? '/admin' : '/dashboard'), { replace: true, state: from?.state });
    } catch (err) { setError(err.message || 'Could not sign in.'); }
    finally { setLoading(false); }
  };
  return <div className="auth-page"><div className="auth-image" aria-hidden="true" /><div className="auth-panel"><div className="auth-panel__inner">
    <span className="eyebrow">Welcome back</span><h1>Keep the journey going.</h1><p>Sign in to return to your saved plans.</p>
    {error && <div className="travel-error" role="alert">{error}</div>}
    <form className="auth-form" onSubmit={submit}>
      <label>Email address<input type="email" autoComplete="email" value={email} onChange={(event) => setEmail(event.target.value)} required /></label>
      <label>Password<input type="password" autoComplete="current-password" value={password} onChange={(event) => setPassword(event.target.value)} required /></label>
      <button type="submit" disabled={loading} className="button button--ink">{loading ? 'Signing in…' : 'Sign in'} <ArrowRight size={17} /></button>
    </form>
    <div className="auth-switch">New to PathYatra? <Link to="/register" state={location.state}>Create an account</Link></div>
  </div></div></div>;
}
