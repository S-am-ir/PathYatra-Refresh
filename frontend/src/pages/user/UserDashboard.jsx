import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { ArrowRight } from 'lucide-react';
import { useAuth } from '../../context/AuthContext';
import api from '../../services/api';
import { formatDate } from '../../utils/formatters';
import './travel.css';
import './account.css';

export default function UserDashboard() {
  const { user } = useAuth();
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  useEffect(() => {
    let active = true;
    api.get('/itinerary/fetch.php').then((res) => { if (active && res.success) setItems(res.data || []); })
      .catch((err) => { if (active) setError(err.message || 'Could not load your trips.'); })
      .finally(() => { if (active) setLoading(false); });
    return () => { active = false; };
  }, []);
  const planned = items.filter((item) => item.status === 'planned').length;
  return <div className="travel-page account-page"><div className="shell">
    <div className="account-welcome"><span className="eyebrow eyebrow--light">Your journey</span><h1>Welcome back,<br /><em>{user?.name || 'traveler'}.</em></h1><p>Your plans for the road ahead, all in one place.</p><Link className="button button--gold" to="/generator">Plan a journey <ArrowRight size={17} /></Link></div>
    {error && <div className="travel-error" role="alert">{error}</div>}
    <div className="account-overview"><div><strong>{loading ? '—' : items.length}</strong><span>Saved plans</span></div><div><strong>{loading ? '—' : planned}</strong><span>Still to come</span></div><div><strong>{loading ? '—' : items.length - planned}</strong><span>Completed</span></div></div>
    <div className="section-heading account-recent"><div><span className="eyebrow">Pick up where you left off</span><h2>Recent journeys.</h2></div><Link to="/my-itineraries" className="text-link">All saved trips <ArrowRight size={16} /></Link></div>
    {loading ? <div className="travel-empty">Loading your plans…</div> : items.length ? <div className="trip-list">{items.slice(0, 3).map((item, index) => <Link className="trip-card trip-card--link" to={`/itinerary/${item.id}`} key={item.id}><span className="trip-card__number">{String(index + 1).padStart(2, '0')}</span><span className="trip-card__body"><span className="eyebrow">{item.status}</span><strong>{item.title}</strong><small>{formatDate(item.start_date)} · {item.total_days} days</small></span><ArrowRight size={20} /></Link>)}</div> : <div className="travel-empty"><h2>Your next journey starts here.</h2><p>You have not saved a plan yet.</p><Link className="button button--ink" to="/generator">Open the planner <ArrowRight size={16} /></Link></div>}
  </div></div>;
}
