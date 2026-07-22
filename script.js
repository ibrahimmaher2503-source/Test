const button = document.getElementById("clickMe");
const counter = document.getElementById("counter");

let clicks = 0;

button.addEventListener("click", () => {
  clicks += 1;
  counter.textContent = `دوست ${clicks} ${clicks === 1 ? "مرة" : "مرات"} 🎉`;
});
