/** "Dit past iets minder goed bij jouw stijl" — adviserend en positief, nooit "fout". */
function renderAvoidSection(container, primaryStyle) {
  if (!primaryStyle?.avoid) return;

  const card = document.createElement("section");
  card.className = "result-card result-avoid";

  const title = document.createElement("h3");
  title.textContent = "Dit past iets minder goed bij jouw stijl";
  card.appendChild(title);

  const text = document.createElement("p");
  text.className = "section-intro";
  text.textContent = primaryStyle.avoid;
  card.appendChild(text);

  container.appendChild(card);
}

export { renderAvoidSection };
