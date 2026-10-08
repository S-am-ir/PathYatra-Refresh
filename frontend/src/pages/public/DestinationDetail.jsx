import React, { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import api from '../../services/api';
import { useAuth } from '../../context/AuthContext';
import { formatNPR } from '../../utils/formatters';
import '../user/travel.css';

export default function DestinationDetail() {
  const { id } = useParams();
  const { user } = useAuth();
  const [place, setPlace] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [rating, setRating] = useState(5);
  const [comment, setComment] = useState('');
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState('');
  useEffect(() => {
    let active = true; setLoading(true); setPlace(null); setError('');
    api.get('/destinations/detail.php', { params: { id } }).then((res) => { if (active) { setPlace(res.data); setRating(res.data.my_review?.rating || 5); setComment(res.data.my_review?.comment || ''); } }).catch((err) => { if (active) setError(err.message); }).finally(() => { if (active) setLoading(false); });
    return () => { active = false; };
  }, [id, user?.id]);
  const submit = async (event) => {
    event.preventDefault(); setBusy(true); setError(''); setMessage('');
    try {
      await api.post('/reviews/submit.php', { destination_id: Number(id), rating: Number(rating), comment });
      const result = await api.get('/destinations/detail.php', { params: { id } }); setPlace(result.data); setMessage('Your review has been saved.');
    } catch (err) { setError(err.message); } finally { setBusy(false); }
  };
  return <div className="travel-page"><div className="shell">
    <Link className="travel-back" to="/destinations">← Explore destinations</Link>
    {error && <p className="travel-error" role="alert">{error}</p>}{loading ? <p role="status">Loading destination…</p> : place && <>
      <div className="travel-heading"><span className="eyebrow">{place.region} / Nepal</span><h1>{place.name}</h1><p>{place.description}</p></div>
      <div className="planner-note">Recommended seasons: {place.suitable_seasons.split(',').join(' · ')}<br />Catalog daily estimate: {formatNPR(place.avg_cost_per_day)}<br />{Number(place.total_reviews) ? `${place.avg_rating} / 5 from ${place.total_reviews} traveler reviews` : 'No traveler reviews yet.'}</div>
      <Link className="button button--ink" to="/generator" state={{ destination: place.id }}>Plan with this place →</Link>
      <section className="detail-section"><h2>Activities in the catalog.</h2><p>Costs and durations are planning estimates; arrange entry, permits and availability locally.</p><div className="detail-grid">{place.activities.map((a) => <article className="day-card" key={a.id}><h3>{a.name}</h3><p>{a.category} · {a.duration_hours} hours · {formatNPR(a.cost_npr)}</p><small>{a.preferred_slot} · {a.suitable_seasons.split(',').join(' / ')}</small></article>)}</div>{!place.activities.length && <p>No activities are listed yet.</p>}</section>
      <section className="detail-section"><h2>Traveler reviews.</h2>
      {place.can_review ? <form className="admin-form" onSubmit={submit}><h3>{place.my_review ? 'Update your review' : 'Review your visit'}</h3><label>Rating<select value={rating} onChange={(e) => setRating(e.target.value)}>{[5, 4, 3, 2, 1].map((n) => <option key={n} value={n}>{n} / 5</option>)}</select></label><label>Your experience<textarea value={comment} onChange={(e) => setComment(e.target.value)} minLength={10} maxLength={2000} required rows={4} /></label><button className="button button--ink" disabled={busy}>{busy ? 'Saving…' : 'Save review'}</button>{message && <p role="status">{message}</p>}</form> : <p>Reviews open after you mark a trip containing this destination complete. {!user && <Link to="/login">Sign in</Link>}</p>}
      {place.reviews.map((r) => <article className="day-card" key={r.id}><strong>{r.reviewer_name} · {r.rating} / 5</strong><p>{r.comment}</p><small>Trip: {r.trip_month_year}</small></article>)}{!place.reviews.length && <p>No reviews yet.</p>}</section>
    </>}
  </div></div>;
}
