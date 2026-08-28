/** "Jouw interieurrecept" — vertaalt de woonstijl naar 5 direct toepasbare onderdelen. */
function renderRecipeCard(container, primaryStyle) {
  if (!primaryStyle?.recipe?.length) return;

  const card = document.createElement("section");
  card.className = "result-card recipe-card";

  const title = document.createElement("h3");
  title.textContent = "Jouw interieurrecept";
  card.appendChild(title);

  const list = document.createElement("dl");
  list.className = "recipe-list";
  primaryStyle.recipe.forEach((item) => {
    const dt = document.createElement("dt");
    dt.textContent = item.label;
    const dd = document.createElement("dd");
    dd.textContent = item.value;
    list.appendChild(dt);
    list.appendChild(dd);
  });
  card.appendChild(list);

  container.appendChild(card);
}

export { renderRecipeCard };
