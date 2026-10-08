import React, { useEffect, useState, useRef } from 'react';
import { Link, useParams } from 'react-router-dom';
import api from '../../services/api';
import { REGIONS, SEASONS, ACTIVITY_CATEGORIES } from '../../utils/constants';
import { formatNPR } from '../../utils/formatters';
import '../user/travel.css';
import './admin.css';

const blankDestination = { name: '', region: 'Hilly', description: '', avg_cost_per_day: 3000, latitude: '', longitude: '', seasons: ['Spring', 'Autumn', 'Winter'] };
const blankActivity = { name: '', destination_id: '', category: 'cultural', duration_hours: 2, cost_npr: 0, preferred_slot: 'Morning', seasons: ['Spring', 'Autumn', 'Winter'] };

export default function AdminCatalog() {
  const { section } = useParams();
  const [rows, setRows] = useState([]);
  const requestVersion = useRef(0);
  const [destinations, setDestinations] = useState([]);
  const [form, setForm] = useState(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  const [message, setMessage] = useState('');
  const [search, setSearch] = useState('');
  const [filterDestination, setFilterDestination] = useState('');
  const travelers = section === 'travelers';
  const valid = ['destinations', 'activities', 'travelers'].includes(section);
  const endpoint = travelers ? '/admin/users.php' : `/${section}/index.php`;
  const load = async () => {
    const version = ++requestVersion.current;
    setError(''); setLoading(true);
    try {
      const [result, places] = await Promise.all([api.get(endpoint), api.get('/destinations/index.php')]);
      if (version !== requestVersion.current) return;
      setRows(result.data); setDestinations(places.data);
    } catch (err) { if (version === requestVersion.current) setError(err.message); }
    finally { if (version === requestVersion.current) setLoading(false); }
  };
  useEffect(() => { setRows([]); setForm(null); setMessage(''); setSearch(''); setFilterDestination(''); if (valid) load(); return () => { requestVersion.current++; }; }, [section]);
  const update = (key) => (event) => setForm((current) => ({ ...current, [key]: event.target.value }));
  const edit = (row) => { setError(''); setMessage(''); setForm({ ...row, seasons: row.suitable_seasons.split(',') }); };
  const save = async (event) => {
    event.preventDefault(); setBusy(true); setError('');
    try {
      await api.post(`/${section}/${form.id ? 'update' : 'create'}.php`, form);
      setMessage(form.id ? 'Record updated.' : 'Record created.'); setForm(null); await load();
    } catch (err) { setError(err.message); }
    finally { setBusy(false); }
  };
  const remove = async (row) => {
    if (!window.confirm(`Delete ${row.name}? ${section === 'destinations' ? 'Its activities and reviews will also be removed. ' : ''}Saved plan snapshots are preserved.`)) return;
    setBusy(true); setError('');
    try { await api.delete(`/${section}/delete.php`, { data: { id: row.id } }); setMessage('Record deleted.'); await load(); }
    catch (err) { setError(err.message); } finally { setBusy(false); }
  };
  const changeStatus = async (row) => {
    setBusy(true); setError('');
    try { await api.post('/admin/status.php', { user_id: row.id, status: row.status === 'active' ? 'inactive' : 'active' }); setMessage('Traveler status updated.'); await load(); }
    catch (err) { setError(err.message); } finally { setBusy(false); }
  };
  const visible = rows.filter((r) => `${r.name} ${r.email || ''}`.toLowerCase().includes(search.toLowerCase()) && (!filterDestination || String(r.destination_id) === filterDestination));
  if (!valid) return <div className="travel-page shell"><h1>Admin page not found.</h1><Link to="/admin">Back to dashboard</Link></div>;
  return <div className="travel-page admin-page"><div className="shell">
    <Link to="/admin" className="travel-back">← Admin dashboard</Link>
    <div className="travel-heading"><span className="eyebrow">Administration</span><h1>{section[0].toUpperCase() + section.slice(1)}.</h1><p>{travelers ? 'Manage access to traveler accounts.' : 'Maintain the catalog used by the planner. Costs are estimates in NPR.'}</p></div>
    <nav className="admin-links">{['destinations', 'activities', 'travelers'].map((s) => <Link to={`/admin/${s}`} key={s}>{s}</Link>)}</nav>
    {error && <div className="travel-error" role="alert">{error}</div>}{message && <p role="status">{message}</p>}
    <div className="admin-toolbar"><label>Search<input value={search} onChange={(e) => setSearch(e.target.value)} type="search" /></label>{section === 'activities' && <label>Destination<select value={filterDestination} onChange={(e) => setFilterDestination(e.target.value)}><option value="">All destinations</option>{destinations.map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}</select></label>}{!travelers && <button type="button" className="button button--ink" onClick={() => { setForm({ ...(section === 'destinations' ? blankDestination : blankActivity) }); setError(''); }}>Add {section === 'destinations' ? 'destination' : 'activity'}</button>}</div>
    {form && <form className="admin-form" onSubmit={save}>
      <h2>{form.id ? 'Edit' : 'Add'} {section === 'destinations' ? 'destination' : 'activity'}</h2>
      <div className="planner-fields"><label>Name<input value={form.name} onChange={update('name')} maxLength={150} minLength={2} required /></label>
      {section === 'destinations' ? <>
        <label>Region<select value={form.region} onChange={update('region')}>{REGIONS.map((r) => <option key={r}>{r}</option>)}</select></label>
        <label>Daily estimate · NPR<input type="number" min="0" max="1000000" step="0.01" value={form.avg_cost_per_day} onChange={update('avg_cost_per_day')} required /></label>
        <label>Latitude<input type="number" min="-90" max="90" step="any" value={form.latitude} onChange={update('latitude')} required /></label>
        <label>Longitude<input type="number" min="-180" max="180" step="any" value={form.longitude} onChange={update('longitude')} required /></label>
      </> : <>
        <label>Destination<select value={form.destination_id} onChange={update('destination_id')} required><option value="">Choose destination</option>{destinations.map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}</select></label>
        <label>Category<select value={form.category} onChange={update('category')}>{ACTIVITY_CATEGORIES.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}</select></label>
        <label>Duration · hours<input type="number" min="0.25" max="8" step="0.25" value={form.duration_hours} onChange={update('duration_hours')} required /></label>
        <label>Cost estimate · NPR<input type="number" min="0" max="1000000" step="0.01" value={form.cost_npr} onChange={update('cost_npr')} required /></label>
        <label>Preferred slot<select value={form.preferred_slot} onChange={update('preferred_slot')}>{['Morning', 'Afternoon', 'Evening'].map((s) => <option key={s}>{s}</option>)}</select></label>
      </>}</div>
      {section === 'destinations' && <label>Description<textarea value={form.description} onChange={update('description')} minLength={10} maxLength={3000} required rows={4} /></label>}
      <fieldset className="admin-seasons"><legend>Suitable seasons</legend>{SEASONS.map((season) => <label key={season}><input type="checkbox" checked={form.seasons.includes(season)} onChange={() => setForm((f) => ({ ...f, seasons: f.seasons.includes(season) ? f.seasons.filter((s) => s !== season) : [...f.seasons, season] }))} />{season === 'Summer' ? 'Summer / monsoon' : season}</label>)}</fieldset>
      <div className="result-actions"><button className="button button--ink" disabled={busy || !form.seasons.length}>Save record</button><button type="button" className="button button--outline" onClick={() => setForm(null)} disabled={busy}>Cancel</button></div>
    </form>}
    {loading ? <p role="status">Loading records…</p> : <div className="admin-table-wrap"><table className="admin-table"><thead><tr><th>Name</th><th>{travelers ? 'Email' : section === 'destinations' ? 'Region' : 'Destination'}</th><th>{travelers ? 'Status' : 'Estimate'}</th><th>Actions</th></tr></thead><tbody>{visible.map((row) => <tr key={row.id}><td>{row.name}</td><td>{travelers ? row.email : section === 'destinations' ? row.region : destinations.find((d) => Number(d.id) === Number(row.destination_id))?.name}</td><td>{travelers ? row.status : formatNPR(row.avg_cost_per_day ?? row.cost_npr)}</td><td>{travelers ? <button type="button" disabled={busy} onClick={() => changeStatus(row)}>{row.status === 'active' ? 'Deactivate' : 'Activate'}</button> : <><button type="button" disabled={busy} onClick={() => edit(row)}>Edit</button><button type="button" disabled={busy} onClick={() => remove(row)}>Delete</button></>}</td></tr>)}</tbody></table>{!visible.length && <p>No matching records.</p>}</div>}
  </div></div>;
}
