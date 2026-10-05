import React, { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { ArrowDown, ArrowRight, Compass, MapPin, MoveUpRight } from 'lucide-react';
import api from '../../services/api';
import { formatNPR } from '../../utils/formatters';
import './home.css';

const regions = { Himalayan: 'High country', Hilly: 'Valleys & hills', Terai: 'Southern plains' };

export default function Home() {
  const navigate = useNavigate();
  const [destinations, setDestinations] = useState([]);
  const [loading, setLoading] = useState(true);
  const [catalogError, setCatalogError] = useState('');
  const [selection, setSelection] = useState('');
  const [days, setDays] = useState(5);
  const [budget, setBudget] = useState(30000);

  useEffect(() => {
    let active = true;
    api.get('/destinations/index.php')
      .then((res) => {
        if (active && res.success && Array.isArray(res.data)) setDestinations(res.data);
      })
      .catch(() => {
        if (active) setCatalogError('The destination catalog is unavailable right now. You can still open the planner.');
      })
      .finally(() => { if (active) setLoading(false); });
    return () => { active = false; };
  }, []);

  const beginPlan = (event) => {
    event.preventDefault();
    navigate('/generator', { state: {
      destination: selection ? Number(selection) : undefined,
      days: Number(days), budget: Number(budget),
    } });
  };

  return (
    <div className="home-page">
      <section className="home-hero" aria-labelledby="home-title">
        <div className="home-hero__image" aria-hidden="true" />
        <div className="home-hero__shade" aria-hidden="true" />
        <div className="home-hero__inner shell">
          <div className="home-hero__copy">
            <span className="eyebrow eyebrow--light"><span className="eyebrow__rule" /> A slower way through Nepal</span>
            <h1 id="home-title">Find your way<br /><em>through Nepal.</em></h1>
            <p>Choose the places you want to see. Shape a day by day plan around your time, interests, and estimated budget.</p>
            <div className="home-hero__actions">
              <Link className="button button--gold" to="/generator">Plan your journey <ArrowRight size={18} /></Link>
              <a className="text-link text-link--light" href="#discover">Explore the places <ArrowDown size={16} /></a>
            </div>
          </div>
          <div className="home-hero__caption"><span>01 / The journey begins here</span><span>नेपाल · Nepal</span></div>
        </div>
      </section>

      <section className="home-planner shell" aria-label="Start a trip plan">
        <div className="home-planner__intro">
          <span className="eyebrow">Start here</span>
          <h2>A few details.<br /><em>A path to follow.</em></h2>
          <p>Set the outline now. You can choose more destinations and interests in the planner.</p>
        </div>
        <form className="home-planner__form" onSubmit={beginPlan}>
          <label>First stop
            <select value={selection} onChange={(event) => setSelection(event.target.value)}>
              <option value="">Choose in the planner</option>
              {destinations.map((place) => <option value={place.id} key={place.id}>{place.name}</option>)}
            </select>
          </label>
          <label>Days away
            <input type="number" min="1" max="30" value={days} onChange={(event) => setDays(event.target.value)} required />
          </label>
          <label>Estimated budget · NPR
            <input type="number" min="3000" step="1000" value={budget} onChange={(event) => setBudget(event.target.value)} required />
          </label>
          <button className="button button--ink" type="submit">Continue to planner <ArrowRight size={17} /></button>
          {catalogError && <p className="home-planner__notice" role="status">{catalogError}</p>}
        </form>
      </section>

      <section className="home-intro shell" id="how-it-works">
        <div className="home-intro__title"><span className="eyebrow">Built for the journey</span><h2>A plan with<br /><em>room to wander.</em></h2></div>
        <div className="home-intro__body">
          <p>PathYatra brings together a small, growing catalog of Nepal destinations and activities. The planner considers your season, interests, trip length, and estimated spending as it builds each day.</p>
          <Link to="/generator" className="text-link">See how your plan takes shape <ArrowRight size={17} /></Link>
        </div>
      </section>

      <section className="home-editorial" aria-label="Scenes inspired by travel in Nepal">
        <div className="home-editorial__grid shell">
          <figure className="home-editorial__large">
            <img src="/images/nepal-courtyard.webp" alt="An old brick courtyard with carved wooden windows" loading="lazy" />
            <figcaption><span>01 / Culture</span><strong>Take the longer way through a place.</strong></figcaption>
          </figure>
          <figure className="home-editorial__small">
            <img src="/images/nepal-river.webp" alt="A quiet river winding through a forested valley" loading="lazy" />
            <figcaption><span>02 / Nature</span><strong>Leave space for what you find.</strong></figcaption>
          </figure>
        </div>
        <p className="home-editorial__note shell">Illustrative imagery inspired by Nepal. Check local conditions before traveling.</p>
      </section>

      <section className="home-discover shell" id="discover">
        <div className="section-heading">
          <div><span className="eyebrow">From the catalog</span><h2>Places to begin.</h2></div>
          <Link className="text-link" to="/destinations">View all destinations <MoveUpRight size={17} /></Link>
        </div>
        {loading ? <p className="home-discover__state" role="status">Opening the destination catalog…</p>
          : catalogError ? <div className="home-discover__state" role="status">{catalogError}</div>
            : destinations.length === 0 ? <div className="home-discover__state">Destinations will appear here when the catalog is ready.</div>
              : <div className="home-discover__grid">
                {destinations.slice(0, 5).map((place, index) => (
                  <Link className="place-card" to="/generator" state={{ destination: place.id }} key={place.id}>
                    <span className="place-card__number">{String(index + 1).padStart(2, '0')}</span>
                    <span className="place-card__region"><MapPin size={14} /> {regions[place.region] || place.region}</span>
                    <h3>{place.name}</h3>
                    <p>{place.description}</p>
                    <span className="place-card__bottom"><span>From {formatNPR(place.avg_cost_per_day)} / day <small>catalog estimate</small></span><ArrowRight size={19} /></span>
                  </Link>
                ))}
              </div>}
      </section>

      <section className="home-method" aria-labelledby="method-title">
        <div className="shell home-method__inner">
          <div><span className="eyebrow eyebrow--light">How it comes together</span><h2 id="method-title">Your choices lead.<br /><em>The planner follows.</em></h2></div>
          <ol>
            <li><span>01</span><div><h3>Choose your places</h3><p>Start with destinations, dates, a budget, and the things you enjoy.</p></div></li>
            <li><span>02</span><div><h3>Find a sensible order</h3><p>Activities are matched to the season and interests. Destinations are ordered using a distance heuristic.</p></div></li>
            <li><span>03</span><div><h3>Take the plan with you</h3><p>Review your day by day outline, download it, or save it to your account.</p></div></li>
          </ol>
        </div>
      </section>
      <section className="home-outro shell"><Compass size={27} strokeWidth={1.5} /><span>Where will you begin?</span><Link className="button button--ink" to="/generator">Make a plan <ArrowRight size={18} /></Link></section>
    </div>
  );
}
