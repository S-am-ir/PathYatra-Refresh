# Catalog data

A fresh database contains 25 destinations and 104 activities. Destinations, descriptions, coordinates, prices, category, duration, slot and season tags are maintained through **Admin → Destinations / Activities**. The planner reads that database on every generation. Accommodation tiers and seasonal advisories are database records too; they can be maintained through SQL without changing the planner.

Tourism descriptions were checked against Nepal Tourism Board destination and activity listings:

- https://ntb.gov.np/en
- https://trade.ntb.gov.np/tourist-destination/hill-stations/
- https://trade.ntb.gov.np/tourist-destination/around-kathmandu/
- https://trade.ntb.gov.np/tourist-activities/pilgrimage/
- https://trade.ntb.gov.np/tourist-destination/pilgrimage-sites-2/

These sources describe places, not this application's prices or reservations. Coordinates are approximate destination centres. Costs and durations are **illustrative planning estimates**, not current entrance fees, hotel quotes, transit schedules or guaranteed offers. Season tags are broad catalog recommendations, not forecasts or safety assessments. Some named excursions need guides, permits and transport arranged separately.

No ratings, reviews, bookings or completed trips are fabricated by the fresh seed. Aggregate ratings are computed from submitted traveler reviews. Older installations preserve their existing records. The seed inserts missing named places and activities once and does not overwrite admin edits. An upgraded original database may therefore contain more than the fresh installation's 104 activities.
