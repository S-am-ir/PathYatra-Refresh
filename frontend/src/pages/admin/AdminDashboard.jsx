import React, { useEffect, useState } from 'react';
import { Bar, BarChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import api from '../../services/api';
import '../user/travel.css';
import './admin.css';

const base = import.meta.env.VITE_PHP_BASE_URL || (import.meta.env.DEV ? 'http://127.0.0.1:8001' : '');
const links = [
  ['Destinations', `${base}/admin/destinations.php`],
  ['Activities', `${base}/admin/activities.php`],
  ['Travelers', `${base}/admin/users.php`],
];

export default function AdminDashboard() {
  const [data, setData] = useState(null);
  const [error, setError] = useState('');
  useEffect(() => {
    let active = true;
    api.get('/admin/analytics.php')
      .then((res) => { if (active && res.success) setData(res.data); })
      .catch((err) => { if (active) setError(err.message || 'Could not load analytics.'); });
    return () => { active = false; };
  }, []);
  const metrics = data?.metrics || {};
  const statCards = [
    ['Destinations', metrics.total_destinations],
    ['Activities', metrics.total_activities],
    ['Travelers', metrics.total_travelers],
    ['Saved itineraries', metrics.total_itineraries],
  ];
  return <div className="travel-page admin-page"><div className="shell">
    <div className="travel-heading"><span className="eyebrow">PathYatra / Administration</span><h1>At a glance.</h1><p>Catalog and traveler activity from the live database.</p></div>
    <nav className="admin-links" aria-label="Catalog administration">{links.map(([label, url]) => <a href={url} key={label}>{label} ↗</a>)}</nav>
    {error && <div role="alert" className="travel-error">{error}</div>}
    <div className="admin-metrics">{statCards.map(([label, value]) => <div key={label}><span>{label}</span><strong>{value ?? '—'}</strong></div>)}</div>
    <section className="admin-chart"><div><span className="eyebrow">Planning activity</span><h2>Destinations in saved plans.</h2></div>
      {data?.top_destinations?.length ? <div className="admin-chart__plot"><ResponsiveContainer width="100%" height="100%"><BarChart data={data.top_destinations} margin={{ top: 20, right: 10, left: -20, bottom: 10 }}><CartesianGrid stroke="#e5dfd2" vertical={false} /><XAxis dataKey="name" tick={{ fill: '#566b5c', fontSize: 12 }} /><YAxis tick={{ fill: '#566b5c', fontSize: 12 }} /><Tooltip /><Bar dataKey="count" fill="#5d8067" /></BarChart></ResponsiveContainer></div> : <p>{error ? 'Analytics are unavailable.' : 'No saved trips yet.'}</p>}
    </section>
  </div></div>;
}
