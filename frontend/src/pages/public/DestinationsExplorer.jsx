import React, { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { ArrowRight, MapPin, Search } from 'lucide-react';
import api from '../../services/api';
import { SEASONS, ACTIVITY_CATEGORIES } from '../../utils/constants';
import { formatNPR } from '../../utils/formatters';
import './home.css';
import './explore.css';
import '../user/travel.css';

export default function DestinationsExplorer() {
  const [destinations, setDestinations] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [query, setQuery] = useState('');
  const [region, setRegion] = useState('');
  const [season, setSeason] = useState('');
  const [category, setCategory] = useState('');
  const [maxBudget, setMaxBudget] = useState('');

  useEffect(() => {
    let active = true;
    setLoading(true); setError('');
    api.get('/destinations/index.php', { params: { ...(region ? { region } : {}), ...(season ? { season } : {}), ...(category ? { category } : {}), ...(maxBudget !== '' ? { max_budget: maxBudget } : {}) } })
      .then((res) => { if (active && res.success && Array.isArray(res.data)) setDestinations(res.data); })
      .catch(() => { if (active) setError('We could not load the destination catalog. Check the server and try again.'); })
      .finally(() => { if (active) setLoading(false); });
    return () => { active = false; };
  }, [region, season, category, maxBudget]);

  const filtered = useMemo(() => destinations.filter((place) => {
    const search = query.trim().toLowerCase();
    return (!region || place.region === region) && (!search || `${place.name} ${place.description}`.toLowerCase().includes(search));
  }), [destinations, query, region]);

  return <div className="explore-page">
    <header className="explore-banner">
      <div className="shell"><span className="eyebrow eyebrow--light">Explore / Nepal</span><h1>Every journey<br /><em>begins somewhere.</em></h1><p>Find a place that speaks to you, then build a plan around it.</p></div>
    </header>
    <section className="shell explore-content" aria-label="Destination catalog">
      <div className="explore-filter">
        <label><Search size={18} /><span className="visually-hidden">Search destinations</span><input type="search" placeholder="Search places or experiences" value={query} onChange={(event) => setQuery(event.target.value)} /></label>
        <label><span className="visually-hidden">Filter by region</span><select value={region} onChange={(event) => setRegion(event.target.value)}><option value="">All regions</option><option value="Himalayan">High country</option><option value="Hilly">Valleys & hills</option><option value="Terai">Southern plains</option></select></label>
        <label><span className="visually-hidden">Filter by season</span><select aria-label="Season" value={season} onChange={(e) => setSeason(e.target.value)}><option value="">All seasons</option>{SEASONS.map((s) => <option key={s}>{s}</option>)}</select></label>
        <label><span className="visually-hidden">Activity category</span><select aria-label="Activity category" value={category} onChange={(e) => setCategory(e.target.value)}><option value="">All activities</option>{ACTIVITY_CATEGORIES.map((c) => <option value={c.id} key={c.id}>{c.name}</option>)}</select></label>
        <label><span className="visually-hidden">Maximum daily estimate</span><input aria-label="Maximum daily estimate" type="number" min="0" max="1000000" placeholder="Max NPR / day" value={maxBudget} onChange={(e) => setMaxBudget(e.target.value)} /></label>
      </div>
      <div className="explore-count">{loading ? 'Opening catalog…' : `${filtered.length} ${filtered.length === 1 ? 'place' : 'places'} to explore`}</div>
      {error ? <div className="travel-empty" role="alert">{error}</div> : !loading && filtered.length === 0 ? <div className="travel-empty">No destinations match that search.</div> :
        <div className="explore-grid">{filtered.map((place, index) => <article className="explore-card" key={place.id}>
          <div className="explore-card__top"><span>{String(index + 1).padStart(2, '0')}</span><span><MapPin size={14} /> {place.region}</span></div>
          <h2>{place.name}</h2><p>{place.description}</p>
          <div className="explore-card__meta"><span>Best in {place.suitable_seasons?.split(',').join(' · ')}</span><span>From {formatNPR(place.avg_cost_per_day)} / day <small>catalog estimate</small></span></div>
          <Link to={`/destinations/${place.id}`}>Activities & reviews <ArrowRight size={17} /></Link>
          <Link to="/generator" state={{ destination: place.id }}>Plan with this place <ArrowRight size={17} /></Link>
        </article>)}</div>}
    </section>
  </div>;
}
