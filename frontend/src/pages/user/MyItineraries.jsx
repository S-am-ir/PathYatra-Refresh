import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { ArrowRight, Download, Trash2 } from 'lucide-react';
import api from '../../services/api';
import { formatDate, formatNPR } from '../../utils/formatters';
import { generateItineraryPDF } from '../../utils/pdfGenerator';
import { useAuth } from '../../context/AuthContext';
import './travel.css';
import './account.css';

export default function MyItineraries() {
  const { user } = useAuth();
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [message, setMessage] = useState('');

  const load = async () => {
    try {
      const res = await api.get('/itinerary/fetch.php');
      if (res.success) setItems(res.data || []);
    } catch (err) { setError(err.message || 'Could not load your trips.'); }
    finally { setLoading(false); }
  };
  useEffect(() => { load(); }, []);

  const complete = async (id) => {
    setError(''); setMessage('');
    try {
      await api.post('/itinerary/complete.php', { id });
      setMessage('Trip marked complete.');
      await load();
    } catch (err) { setError(err.message || 'Could not update the trip.'); }
  };
  const remove = async (id) => {
    if (!window.confirm('Delete this saved trip? This cannot be undone.')) return;
    setError(''); setMessage('');
    try {
      await api.delete(`/itinerary/delete.php?id=${id}`);
      setMessage('Trip deleted.');
      await load();
    } catch (err) { setError(err.message || 'Could not delete the trip.'); }
  };
  const download = async (id) => {
    try {
      const res = await api.get(`/itinerary/fetch.php?id=${id}`);
      if (res.success) generateItineraryPDF(res.data, user?.name || 'Traveler');
    } catch (err) { setError(err.message || 'Could not download the trip.'); }
  };

  return <div className="travel-page account-page"><div className="shell">
    <div className="account-heading"><div><span className="eyebrow">Your journey</span><h1>Saved trips.</h1><p>Return to a plan whenever you need it.</p></div><Link className="button button--ink" to="/generator">Plan another trip <ArrowRight size={17} /></Link></div>
    {error && <div role="alert" className="travel-error">{error}</div>}
    {message && <div role="status" className="account-message">{message}</div>}
    {loading ? <div className="travel-empty">Loading your trips…</div> : items.length === 0 ? <div className="travel-empty"><h2>No saved trips yet.</h2><p>Create a plan, then save it here for later.</p><Link className="button button--ink" to="/generator">Open the planner <ArrowRight size={17} /></Link></div> :
      <div className="trip-list">{items.map((item, index) => <article className="trip-card" key={item.id}>
        <div className="trip-card__number">{String(index + 1).padStart(2, '0')}</div>
        <div className="trip-card__body"><span className="eyebrow">{item.status === 'completed' ? 'Completed journey' : 'Planned journey'}</span><h2><Link to={`/itinerary/${item.id}`}>{item.title}</Link></h2><p>{formatDate(item.start_date)} – {formatDate(item.end_date)} · {item.total_days} days · {item.season}</p><small>Estimated stay + activities {formatNPR(item.estimated_cost)} · Budget {formatNPR(item.total_budget)}</small></div>
        <div className="trip-card__actions"><Link to={`/itinerary/${item.id}`} className="trip-card__view">View plan <ArrowRight size={16} /></Link><button type="button" onClick={() => download(item.id)}><Download size={16} /> PDF</button>{item.status === 'planned' && <button type="button" onClick={() => complete(item.id)} title="Available on or after the final trip date">Mark complete</button>}<button type="button" className="trip-card__delete" aria-label={`Delete ${item.title}`} onClick={() => remove(item.id)}><Trash2 size={16} /></button></div>
      </article>)}</div>}
  </div></div>;
}
