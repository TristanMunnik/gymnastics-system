export default function StatusBadge({ status }) {
  // 'On Hold' becomes 'onhold' so it matches the CSS class .badge-onhold.
  const className = status.toLowerCase().replace(/\s+/g, '');
  return <span className={`badge badge-${className}`}>{status}</span>;
}
