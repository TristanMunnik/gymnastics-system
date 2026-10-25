export default function StatusBadge({ status }) {
  const className = status.toLowerCase().replace(/\s+/g, '');
  return <span className={`badge badge-${className}`}>{status}</span>;
}
