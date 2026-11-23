// Debounced search
export function debounce(fn, wait = 200) {
  let timer = null;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), wait);
  };
}

const query = document.querySelector('#search');
query.addEventListener('input', debounce(() => console.log(query.value, 42)));
