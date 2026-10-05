import { jsPDF } from 'jspdf';
import { formatNPR } from './formatters.js';
import { normalizeItinerary } from './itinerary.js';

export function generateItineraryPDF(rawPlan, travelerName = 'Traveler') {
  const plan = normalizeItinerary(rawPlan);
  const doc = new jsPDF({ unit: 'mm', format: 'a4' });
  const width = doc.internal.pageSize.getWidth();
  const height = doc.internal.pageSize.getHeight();
  const margin = 18;
  const textWidth = width - margin * 2;

  doc.setFillColor(27, 56, 46);
  doc.rect(0, 0, width, 51, 'F');
  doc.setTextColor(255, 250, 240);
  doc.setFont('helvetica', 'bold'); doc.setFontSize(25);
  doc.text('PathYatra', margin, 27);
  doc.setFont('helvetica', 'normal'); doc.setFontSize(10);
  doc.text('A personal planning outline for Nepal', margin, 38);

  let y = 66;
  doc.setTextColor(27, 56, 46);
  doc.setFont('helvetica', 'bold'); doc.setFontSize(18);
  const titleLines = doc.splitTextToSize(plan.title || 'Nepal travel itinerary', textWidth);
  doc.text(titleLines, margin, y); y += titleLines.length * 8 + 4;

  doc.setFont('helvetica', 'normal'); doc.setFontSize(10);
  doc.setTextColor(78, 91, 80);
  [
    `Prepared for: ${travelerName}`,
    `Dates: ${plan.start_date} to ${plan.end_date} | ${plan.total_days} days | ${plan.season}`,
    `Budget: ${formatNPR(plan.budget_summary?.total_budget)} | Estimated stay + activities: ${formatNPR(plan.budget_summary?.total_estimated)}`,
  ].forEach((line) => { const rows = doc.splitTextToSize(line, textWidth); doc.text(rows, margin, y); y += rows.length * 5.5 + 2; });
  y += 5;
  doc.setDrawColor(193, 200, 185); doc.line(margin, y, width - margin, y); y += 10;

  const nextPage = (needed) => {
    if (y + needed < height - 20) return;
    doc.addPage(); y = 23;
  };
  for (const day of plan.days || []) {
    nextPage(34);
    doc.setFont('helvetica', 'bold'); doc.setFontSize(13); doc.setTextColor(27, 56, 46);
    const header = doc.splitTextToSize(`Day ${day.day_number}  |  ${day.destination}  |  ${day.date}`, textWidth);
    doc.text(header, margin, y); y += header.length * 6 + 4;
    doc.setFont('helvetica', 'normal'); doc.setFontSize(9.5); doc.setTextColor(65, 76, 67);
    for (const name of ['morning', 'afternoon', 'evening']) {
      const slot = day.slots?.[name];
      const line = `${name[0].toUpperCase() + name.slice(1)}: ${slot?.activity || 'Free time'}  |  ${formatNPR(slot?.cost || 0)}`;
      const rows = doc.splitTextToSize(line, textWidth - 5);
      nextPage(rows.length * 5 + 2);
      doc.text(rows, margin + 3, y); y += rows.length * 5 + 2;
    }
    const stay = doc.splitTextToSize(`Estimated stay: ${day.accommodation?.name || 'Accommodation'} (${formatNPR(day.accommodation?.cost || 0)})  |  Day total: ${formatNPR(day.day_total)}`, textWidth - 5);
    nextPage(stay.length * 5 + 8);
    doc.setFont('helvetica', 'italic'); doc.text(stay, margin + 3, y); y += stay.length * 5 + 11;
  }

  nextPage(16);
  doc.setFont('helvetica', 'normal'); doc.setFontSize(8); doc.setTextColor(115, 110, 97);
  doc.text(doc.splitTextToSize('Planning estimates only. Transport, meals, live hotel prices, availability and road conditions are not included. Confirm before travel.', textWidth), margin, y);
  const pageCount = doc.internal.getNumberOfPages();
  for (let page = 1; page <= pageCount; page++) {
    doc.setPage(page); doc.setFont('helvetica', 'normal'); doc.setFontSize(8); doc.setTextColor(126, 137, 125);
    doc.text(`PathYatra | ${page} / ${pageCount}`, width / 2, height - 10, { align: 'center' });
  }
  const filename = (plan.title || 'Itinerary').replace(/[^a-zA-Z0-9]+/g, '_').slice(0, 60);
  doc.save(`PathYatra_${filename}_${plan.start_date || 'Plan'}.pdf`);
}
