/** "Dit typeert jouw woonstijl" — voelt aan als resultaat van de keuzes, niet als losse feitjes. */
function renderTraitsSection(container, primaryStyle) {
  if (!primaryStyle?.traits?.length) return;

  const card = document.createElement("section");
  card.className = "result-card";

  const title = document.createElement("h3");
  title.textContent = "Dit typeert jouw woonstijl";
  card.appendChild(title);

  if (primaryStyle.traitsIntro) {
    const intro = document.createElement("p");
    intro.className = "section-intro";
    intro.textContent = primaryStyle.traitsIntro;
    card.appendChild(intro);
  }

  const list = document.createElement("ul");
  list.className = "trait-list";
  primaryStyle.traits.forEach((trait) => {
    const li = document.createElement("li");
    li.textContent = trait;
    list.appendChild(li);
  });
  card.appendChild(list);

  container.appendChild(card);
}

export { renderTraitsSection };
