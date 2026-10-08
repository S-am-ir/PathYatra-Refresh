import React, { useEffect, useMemo, useState } from 'react';
import { Link, useLocation, useNavigate, useParams } from 'react-router-dom';
import { MapContainer, TileLayer, CircleMarker, Polyline, Tooltip, useMap } from 'react-leaflet';
import 'leaflet/dist/leaflet.css';
import { ArrowLeft, ArrowRight, Download, MapPin, Save } from 'lucide-react';
import { useAuth } from '../../context/AuthContext';
import api from '../../services/api';
import { formatDate, formatNPR } from '../../utils/formatters';
import { generateItineraryPDF } from '../../utils/pdfGenerator';
import { normalizeItinerary } from '../../utils/itinerary';
import './travel.css';

function FitRoute({ points }) {
  const map = useMap();
  useEffect(() => {
    if (points.length > 1) map.fitBounds(points, { padding: [38, 38], animate: false });
    else if (points.length) map.setView(points[0], 9, { animate: false });
  }, [map, points]);
  return null;
}

function RouteMap({ destinations, origin }) {
  const mappedStops = useMemo(() => destinations.map((stop) => ({ stop, point: [Number(stop.latitude), Number(stop.longitude)] }))
    .filter(({ stop, point: [lat, lng] }) => stop.latitude != null && stop.longitude != null && Number.isFinite(lat) && Number.isFinite(lng)), [destinations]);
  const points = useMemo(() => [...(origin ? [[Number(origin.latitude), Number(origin.longitude)]] : []), ...mappedStops.map(({ point }) => point)], [origin, mappedStops]);
  if (!points.length) return null;
  return <div className="result-map">
    {/* Leaflet 1.9's zoom timer can outlive a removed map during route navigation. */}
    <MapContainer center={points[0]} zoom={7} zoomAnimation={false} scrollWheelZoom={false} className="result-map__canvas">
      <TileLayer attribution='&copy; OpenStreetMap contributors' url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" />
      {origin && <CircleMarker center={points[0]} radius={7} pathOptions={{ color: '#ad7646' }}><Tooltip>{origin.label}</Tooltip></CircleMarker>}
      {points.length > 1 && <Polyline positions={points} pathOptions={{ color: '#ad7646', weight: 3, dashArray: '7 7' }} />}
      {mappedStops.map(({ point, stop }, index) => <CircleMarker key={stop.id || index} center={point} radius={8} pathOptions={{ color: '#f7f1e6', weight: 2, fillColor: '#1b382e', fillOpacity: 1 }}>
        <Tooltip direction="top" offset={[0, -7]}>{index + 1}. {stop.name}</Tooltip>
      </CircleMarker>)}
      <FitRoute points={points} />
    </MapContainer>
    <p>Approximate destination order · connecting lines are not road directions</p>
  </div>;
}

export default function ItineraryResult() {
  const location = useLocation();
  const navigate = useNavigate();
  const { id } = useParams();
  const { user } = useAuth();
  const [plan, setPlan] = useState(() => {
    if (id) return null;
    try { return normalizeItinerary(location.state?.plan || JSON.parse(sessionStorage.getItem('pathyatra_latest_plan') || 'null')); }
    catch { return normalizeItinerary(location.state?.plan); }
  });
  const [loading, setLoading] = useState(Boolean(id));
  const [saving, setSaving] = useState(false);
  const [savedId, setSavedId] = useState(id || null);
  const [error, setError] = useState('');

  useEffect(() => {
    if (!id) return;
    let active = true;
    api.get(`/itinerary/fetch.php?id=${encodeURIComponent(id)}`)
      .then((res) => { if (active) { if (res.success) setPlan(normalizeItinerary(res.data)); else setError(res.message || 'Could not load this itinerary.'); } })
      .catch((err) => { if (active) setError(err.message || 'Could not load this itinerary.'); })
      .finally(() => { if (active) setLoading(false); });
    return () => { active = false; };
  }, [id]);

  const save = async () => {
    if (!user) { navigate('/login', { state: { from: location } }); return; }
    setSaving(true); setError('');
    try {
      const res = await api.post('/itinerary/save.php', { plan_token: plan.plan_token });
      if (!res.success) throw new Error(res.message || 'Could not save the plan.');
      setSavedId(res.data.itinerary_id);
    } catch (err) { setError(err.message || 'Could not save the plan.'); }
    finally { setSaving(false); }
  };

  if (loading) return <div className="travel-page shell travel-empty">Loading your trip…</div>;
  if (!plan) return <div className="travel-page shell travel-empty"><h1>We could not find that plan.</h1><p>{error || 'Create a new itinerary to get started.'}</p><Link className="button button--ink" to="/generator">Open the planner <ArrowRight size={16} /></Link></div>;

  const summary = plan.budget_summary || {};
  const stops = plan.map_data?.destinations || [];
  return <div className="travel-page result-page"><div className="shell">
    <Link to={id ? '/my-itineraries' : '/generator'} className="travel-back"><ArrowLeft size={16} /> {id ? 'My trips' : 'Back to planner'}</Link>
    <div className="result-header">
      <div><span className="eyebrow">Your journey / {id ? 'Saved plan' : 'New plan'}</span><h1>{plan.title}</h1><p>{plan.total_days} days · {plan.season} · {formatDate(plan.start_date)} – {formatDate(plan.end_date)}</p></div>
      <div className="result-actions">
        <button type="button" className="button button--outline" onClick={() => generateItineraryPDF(plan, user?.name || 'Traveler')}><Download size={17} /> Download PDF</button>
        {savedId ? <Link className="button button--ink" to={`/itinerary/${savedId}`}>Saved plan <ArrowRight size={17} /></Link>
          : <button type="button" className="button button--ink" disabled={saving} onClick={save}><Save size={17} /> {saving ? 'Saving…' : user ? 'Save this plan' : 'Sign in to save'}</button>}
      </div>
    </div>
    {error && <div className="travel-error" role="alert">{error}</div>}
    <div className="result-estimate">
      <div><span>Trip budget</span><strong>{formatNPR(summary.total_budget)}</strong></div>
      <div><span>Estimated stay + activities</span><strong>{formatNPR(summary.total_estimated)}</strong></div>
      <div><span>Unallocated</span><strong>{formatNPR(summary.remaining)}</strong></div>
    </div>
    <p className="result-caveat">Planning estimates only. Transport, meals, live hotel prices, and availability are not included. Confirm conditions before travel.</p>
    {plan.weather_advisory && <div className="result-advisory"><span>Season note</span><p>{plan.weather_advisory}</p></div>}
    {plan.travel_note && <div className="planner-note">{plan.travel_note}</div>}
    {plan.warnings?.map((warning) => <p className="planner-note" key={warning}>{warning}</p>)}
    {plan.status === 'completed' && <div className="planner-note">Your trip is complete. Review a visited destination: {stops.map((stop) => <Link key={stop.id} to={`/destinations/${stop.id}`}>{stop.name} · </Link>)}</div>}
    <div className="result-layout">
      <section className="result-days" aria-label="Day by day itinerary">
        <div className="result-section-heading"><span className="eyebrow">Day by day</span><h2>The outline.</h2></div>
        {plan.days?.map((day) => <article className="day-card" key={day.day_number}>
          <div className="day-card__top"><span className="day-card__number">{String(day.day_number).padStart(2, '0')}</span><div><small>{formatDate(day.date)}</small><h3>{day.destination}</h3></div><strong>{formatNPR(day.day_total)}</strong></div>
          <div className="day-card__slots">{['morning', 'afternoon', 'evening'].map((slotName) => {
            const slot = day.slots?.[slotName];
            return <div className="day-card__slot" key={slotName}><span>{slotName}</span><div><strong>{slot?.activity || 'Free time'}</strong>{slot?.duration && <small>{slot.duration} hours</small>}</div><span>{slot?.cost ? formatNPR(slot.cost) : 'Free'}</span></div>;
          })}</div>
          <div className="day-card__stay">Estimated stay <span>{day.accommodation?.name || 'Accommodation estimate'} · {formatNPR(day.accommodation?.cost || 0)}</span></div>
        </article>)}
      </section>
      <aside className="result-route"><div className="result-route__heading"><MapPin size={18} /><div><span className="eyebrow">The route</span><h2>Your stops.</h2></div></div>
        <RouteMap destinations={stops} origin={plan.origin} />
        <ol>{stops.map((stop, index) => <li key={stop.id || index}><span>{String(index + 1).padStart(2, '0')}</span>{stop.name}</li>)}</ol>
        {!stops.length && <p>Route coordinates are unavailable for this saved plan.</p>}
      </aside>
    </div>
  </div></div>;
}
