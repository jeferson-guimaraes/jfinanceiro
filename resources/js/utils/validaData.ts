export default function isValidDate(value: string): boolean {
  if (!value) return false;

  const [year, month, day] = value.split('-').map(Number);

  const date = new Date(year, month - 1, day);

  const isRealDate =
    date.getFullYear() === year &&
    date.getMonth() === month - 1 &&
    date.getDate() === day;

  if (!isRealDate) return false;

  const currentYear = new Date().getFullYear();

  if (year < 2000 || year > currentYear + 10) {
    return false;
  }

  return true;
}