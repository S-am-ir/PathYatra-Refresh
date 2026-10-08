import React, { useEffect, useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { ArrowLeft, ArrowRight, Check, MapPin } from 'lucide-react';
import api from '../../services/api';
import { formatNPR } from '../../utils/formatters';
import { ACTIVITY_CATEGORIES } from '../../utils/constants';
import './travel.css';

const steps = ['Places', 'Dates', 'Budget', 'Interests'];
const localDate = (daysFromNow) => {
  const date = new Date();
  date.setDate(date.getDate() + daysFromNow);
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
};

export default function GeneratorPage() {
  const navigate = useNavigate();
  const location = useLocation();
  const [step, setStep] = useState(0);
  const [destinations, setDestinations] = useState([]);
  const [selected, setSelected] = useState([]);
  const [startDate, setStartDate] = useState(localDate(7));
  const [days, setDays] = useState(Number(location.state?.days) || 5);
  const [budget, setBudget] = useState(Number(location.state?.budget) || 30000);
  const [interests, setInterests] = useState(['cultural', 'nature']);
  const [loading, setLoading] = useState(false);
  const [catalogLoading, setCatalogLoading] = useState(true);
  const [error, setError] = useState('');
  const [origin, setOrigin] = useState(null);
  const [locating, setLocating] = useState(false);
  const [locationMessage, setLocationMessage] = useState('');
  const [manualStart, setManualStart] = useState('');

  useEffect(() => {
    let active = true;
    api.get('/destinations/index.php')
      .then((res) => {
        if (!active) return;
        if (res.success && Array.isArray(res.data)) {
          setDestinations(res.data);
          const prefill = Number(location.state?.destination);
          if (res.data.some((place) => Number(place.id) === prefill)) setSelected([prefill]);
        } else setError('The destination catalog could not be loaded.');
      })
      .catch(() => { if (active) setError('The destination catalog could not be loaded. Please check the server and try again.'); })
      .finally(() => { if (active) setCatalogLoading(false); });
    return () => { active = false; };
  }, [location.state?.destination]);

  const locate = () => {
    if (!navigator.geolocation) { setLocationMessage('Location is unavailable in this browser. Choose a starting place below.'); return; }
    setLocating(true); setLocationMessage('');
    navigator.geolocation.getCurrentPosition(({ coords }) => {
      setOrigin({ latitude: coords.latitude, longitude: coords.longitude, label: 'Current location' });
      setManualStart(''); setLocationMessage('Current location set as your origin.'); setLocating(false);
    }, () => { setLocationMessage('Location could not be obtained. Allow browser permission on HTTPS or localhost, or choose a starting place below.'); setLocating(false); }, { enableHighAccuracy: true, timeout: 12000, maximumAge: 60000 });
  };

  const toggle = (id) => {
    setError('');
    setSelected((current) => current.includes(id) ? current.filter((item) => item !== id) : [...current, id]);
  };
  const toggleInterest = (id) => setInterests((current) => current.includes(id)
    ? (current.length > 1 ? current.filter((item) => item !== id) : current) : [...current, id]);

  const next = () => {
    if (step === 0 && !selected.length) { setError('Choose at least one destination to continue.'); return; }
    if (step === 1 && (!startDate || startDate < localDate(0))) { setError('Choose a valid start date from today onward.'); return; }
    if (step === 1 && (!Number.isInteger(Number(days)) || Number(days) < 1 || Number(days) > 30)) { setError('Choose a trip length between 1 and 30 days.'); return; }
    if (step === 1 && Number(days) < selected.length) { setError('Allow at least one day for each selected destination.'); return; }
    if (step === 2 && (!Number.isFinite(Number(budget)) || Number(budget) < 1500)) { setError('Set a total budget of at least NPR 1,500.'); return; }
    if (step === 2 && Number(budget) / Number(days) < 1500) { setError('Allow at least NPR 1,500 per day for the estimated stay, or shorten the trip.'); return; }
    setError('');
    setStep((current) => current + 1);
  };

  const generate = async () => {
    if (loading) return;
    setLoading(true); setError('');
    try {
      const res = await api.post('/itinerary/generate.php', {
        destinations: selected, start_date: startDate, days: Number(days), budget: Number(budget), interests, ...(origin ? { origin } : {}),
      });
      if (!res.success) throw new Error(res.message || 'Could not create a plan.');
      sessionStorage.setItem('pathyatra_latest_plan', JSON.stringify(res.data));
      navigate('/itinerary-result', { state: { plan: res.data } });
    } catch (err) {
      setError(err.message || 'Could not create a plan. Please try again.');
    } finally { setLoading(false); }
  };

  return (
    <div className="travel-page planner-page">
      <div className="shell">
        <div className="travel-heading"><span className="eyebrow">Your journey / 01</span><h1>Make it yours.</h1><p>Start with the essentials. The result is a planning outline with estimated costs, ready for you to review.</p></div>
        <div className="planner-layout">
          <aside className="planner-steps" aria-label="Planning steps">
            <span className="eyebrow">Plan your trip</span>
            {steps.map((label, index) => <button type="button" className={index === step ? 'planner-step planner-step--active' : 'planner-step'} onClick={() => { if (index < step) { setStep(index); setError(''); } }} disabled={index > step} key={label}>
              <span>{String(index + 1).padStart(2, '0')}</span>{label}{index < step && <Check size={16} />}
            </button>)}
            <div className="planner-summary"><span>Your outline</span><strong>{selected.length || '—'} places · {days} days</strong><small>{formatNPR(budget)} estimated budget</small></div>
          </aside>
          <div className="planner-panel">
            <div className="planner-panel__top"><span>Step {step + 1} of 4</span><span>{steps[step]}</span></div>
            {error && <div className="travel-error" role="alert">{error}</div>}
            {step === 0 && <div>
              <h2>Where will you go?</h2><p>Choose one or more places. With an origin, the closest selected stop comes first; otherwise the first place you select starts the route.</p>
              <div className="planner-note origin-controls"><strong>Starting point</strong><button type="button" className="button button--outline" onClick={locate} disabled={locating}>{locating ? 'Finding location…' : 'Use my current location'}</button>
                <label>Or choose a starting place<select value={manualStart} onChange={(event) => { const value = event.target.value; setManualStart(value); const place = destinations.find((d) => String(d.id) === value); setOrigin(place ? { latitude: Number(place.latitude), longitude: Number(place.longitude), label: place.name } : null); setLocationMessage(''); }}><option value="">First selected destination</option>{destinations.map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}</select></label>
                {origin && <p>Origin: {origin.label} · {Number(origin.latitude).toFixed(4)}, {Number(origin.longitude).toFixed(4)} <button type="button" onClick={() => { setOrigin(null); setManualStart(''); setLocationMessage(''); }}>Clear</button></p>}
                {locationMessage && <p role="status">{locationMessage}</p>}<small>Location is requested only when you tap the button. It sets route order; travel to the first stop is not scheduled.</small>
              </div>
              {catalogLoading ? <div className="travel-empty">Loading destinations…</div> : destinations.length === 0 ? <div className="travel-empty">No destinations are available right now.</div> :
                <div className="planner-options">{destinations.map((place) => {
                  const id = Number(place.id), active = selected.includes(id);
                  return <button type="button" aria-pressed={active} onClick={() => toggle(id)} className={active ? 'planner-option planner-option--active' : 'planner-option'} key={id}>
                    <span className="planner-option__icon"><MapPin size={18} /></span><span className="planner-option__body"><strong>{place.name}</strong><small>{place.region} · catalog estimate {formatNPR(place.avg_cost_per_day)}/day</small></span>{active && <Check size={18} />}
                  </button>;
                })}</div>}
            </div>}
            {step === 1 && <div><h2>When are you traveling?</h2><p>The trip month helps filter activities by their catalog season tags.</p>
              <div className="planner-fields"><label>Start date<input type="date" min={localDate(0)} value={startDate} onChange={(event) => setStartDate(event.target.value)} required /></label>
              <label>Number of days<input type="number" min={Math.max(1, selected.length)} max="30" value={days} onChange={(event) => setDays(event.target.value)} required /></label></div>
              <div className="planner-note">Allow at least one day per destination. Longer intercity legs reserve transfer days. The planner may ask for more days after it calculates your route.</div>
            </div>}
            {step === 2 && <div><h2>Set an estimated budget.</h2><p>Enter a total in Nepali rupees. The current planner estimates accommodation by tier and selects activities within a daily allowance.</p>
              <div className="planner-fields"><label>Trip budget · NPR<input type="number" min="1500" step="500" value={budget} onChange={(event) => setBudget(event.target.value)} required /></label>
              <div className="planner-budget"><span>About</span><strong>{formatNPR(Number(budget) / Number(days || 1))}</strong><span>per day</span></div></div>
              <div className="planner-note">Estimates exclude transport, meals, and live hotel availability. Confirm local prices before booking.</div>
            </div>}
            {step === 3 && <div><h2>What draws you in?</h2><p>Select at least one interest. These preferences guide the activity ranking.</p>
              <div className="planner-options planner-options--interests">{ACTIVITY_CATEGORIES.map((item) => {
                const active = interests.includes(item.id);
                return <button key={item.id} type="button" aria-pressed={active} className={active ? 'planner-option planner-option--active' : 'planner-option'} onClick={() => toggleInterest(item.id)}><span className="planner-option__body"><strong>{item.name}</strong></span>{active && <Check size={18} />}</button>;
              })}</div>
            </div>}
            <div className="planner-controls">
              <button type="button" className="button button--outline" disabled={step === 0} onClick={() => { setStep(step - 1); setError(''); }}><ArrowLeft size={16} /> Back</button>
              {step < 3 ? <button type="button" className="button button--ink" disabled={catalogLoading || (step === 0 && !destinations.length)} onClick={next}>Continue <ArrowRight size={16} /></button>
                : <button type="button" className="button button--ink" disabled={loading} onClick={generate}>{loading ? 'Building your plan…' : 'Create my plan'} <ArrowRight size={16} /></button>}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
