// The generator and saved-plan endpoint have different shapes. Keep the UI on one shape.
export function normalizeItinerary(plan) {
  if (!plan) return null;
  if (!Array.isArray(plan.days)) return plan;
  const days = plan.days.map((day) => {
    if (!Array.isArray(day.slots)) return day;
    const slots = Object.fromEntries(day.slots.map((slot) => [String(slot.slot_name).toLowerCase(), {
      activity_id: slot.activity_id,
      activity: slot.activity_name,
      duration: Number(slot.duration_hours),
      cost: Number(slot.cost_npr),
    }]));
    return {
      ...day,
      date: day.day_date,
      destination: day.destination_name,
      accommodation: { name: day.accommodation_name || 'Estimated stay', cost: Number(day.accommodation_cost) },
      day_total: Number(day.day_total),
      slots,
    };
  });
  const stops = [];
  for (const day of days) {
    if (day.latitude != null && day.longitude != null && !stops.some((stop) => stop.id === day.destination_id)) {
      stops.push({ id: day.destination_id, name: day.destination, latitude: Number(day.latitude), longitude: Number(day.longitude) });
    }
  }
  return {
    ...plan,
    total_days: Number(plan.total_days),
    budget_summary: plan.budget_summary || {
      total_budget: Number(plan.total_budget),
      total_estimated: Number(plan.estimated_cost),
      remaining: Number(plan.total_budget) - Number(plan.estimated_cost),
    },
    map_data: plan.map_data || { destinations: stops, route: stops.map((stop) => [stop.latitude, stop.longitude]) },
    days,
  };
}
