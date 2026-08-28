/** "Kies meubels met deze uitstraling" — concreet, visueel advies i.p.v. een platte tekstlijst. */
function renderFurnitureAdvice(container, primaryStyle) {
  if (!primaryStyle?.furnitureAdvice?.items?.length) return;

  const card = document.createElement("section");
  card.className = "result-card";

  const title = document.createElement("h3");
  title.textContent = "Kies meubels met deze uitstraling";
  card.appendChild(title);

  if (primaryStyle.furnitureAdvice.intro) {
    const intro = document.createElement("p");
    intro.className = "section-intro";
    intro.textContent = primaryStyle.furnitureAdvice.intro;
    card.appendChild(intro);
  }

  const grid = document.createElement("div");
  grid.className = "furniture-advice-grid";
  primaryStyle.furnitureAdvice.items.forEach((item, index) => {
    const tile = document.createElement("div");
    tile.className = `furniture-advice-tile ph-${index % 6}`;
    tile.textContent = item;
    grid.appendChild(tile);
  });
  card.appendChild(grid);

  container.appendChild(card);
}

export { renderFurnitureAdvice };
