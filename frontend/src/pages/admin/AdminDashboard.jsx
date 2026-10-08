import { Link } from 'react-router-dom';
import React, { useEffect, useState } from 'react';
import { Bar, BarChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import api from '../../services/api';
import '../user/travel.css';
import './admin.css';

const links = [['Destinations', '/admin/destinations'], ['Activities', '/admin/activities'], ['Travelers', '/admin/travelers']];

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
    ['Traveler reviews', metrics.total_reviews],
  ];
  return <div className="travel-page admin-page"><div className="shell">
    <div className="travel-heading"><span className="eyebrow">PathYatra / Administration</span><h1>At a glance.</h1><p>Catalog and traveler activity from the live database.</p></div>
    <nav className="admin-links" aria-label="Catalog administration">{links.map(([label, url]) => <Link to={url} key={label}>{label} →</Link>)}</nav>
    {error && <div role="alert" className="travel-error">{error}</div>}
    <div className="admin-metrics">{statCards.map(([label, value]) => <div key={label}><span>{label}</span><strong>{value ?? '—'}</strong></div>)}</div>
    <section className="admin-chart"><div><span className="eyebrow">Planning activity</span><h2>Destinations in saved plans.</h2></div>
      {data?.top_destinations?.length ? <div className="admin-chart__plot"><ResponsiveContainer width="100%" height="100%"><BarChart data={data.top_destinations} margin={{ top: 20, right: 10, left: -20, bottom: 10 }}><CartesianGrid stroke="#e5dfd2" vertical={false} /><XAxis dataKey="name" tick={{ fill: '#566b5c', fontSize: 12 }} /><YAxis tick={{ fill: '#566b5c', fontSize: 12 }} /><Tooltip /><Bar dataKey="count" fill="#5d8067" /></BarChart></ResponsiveContainer></div> : <p>{error ? 'Analytics are unavailable.' : 'No saved trips yet.'}</p>}
    </section>
    <div className="detail-grid">{[['Budget tiers', data?.budget_tiers, 'value'], ['Activity categories', data?.category_distribution?.map((c) => ({ name: c.category, count: Number(c.count) })), 'count']].map(([title, values, key]) => <section className="admin-chart" key={title}><h2>{title}.</h2>{values?.some((v) => Number(v[key]) > 0) ? <div className="admin-chart__plot"><ResponsiveContainer width="100%" height="100%"><BarChart data={values} layout="vertical" margin={{ left: 10, right: 20 }}><XAxis type="number" allowDecimals={false} /><YAxis type="category" dataKey="name" width={120} tick={{ fontSize: 10 }} /><Tooltip /><Bar dataKey={key} fill="#5d8067" /></BarChart></ResponsiveContainer></div> : <p>No data yet.</p>}</section>)}</div>
  </div></div>;
}
