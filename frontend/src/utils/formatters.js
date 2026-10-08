export const formatNPR = (amount) => {
  if (isNaN(amount)) return 'NPR 0';
  return `NPR ${Number(amount).toLocaleString('en-IN')}`;
};

export const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(/^\d{4}-\d{2}-\d{2}$/.test(dateString) ? `${dateString}T12:00:00` : dateString);
  return date.toLocaleDateString('en-US', {
    weekday: 'short',
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};
