import { createImageTile } from "./imageTile.js";

/** "Materialen die bij jou passen" — losse kaarten met (voorlopig placeholder-)textuurbeelden. */
function renderMaterialsSection(container, primaryStyle) {
  if (!primaryStyle?.materials?.length) return;

  const card = document.createElement("section");
  card.className = "result-card";

  const title = document.createElement("h3");
  title.textContent = "Materialen die bij jou passen";
  card.appendChild(title);

  const grid = document.createElement("div");
  grid.className = "materials-grid-visual";
  primaryStyle.materials.forEach((material, index) => {
    const item = document.createElement("figure");
    item.className = "material-item";

    const image = createImageTile({
      src: material.image,
      alt: material.name,
      label: material.name,
      tintKey: index,
      className: "material-item-image",
    });
    item.appendChild(image);

    const caption = document.createElement("figcaption");
    caption.textContent = material.name;
    item.appendChild(caption);

    grid.appendChild(item);
  });
  card.appendChild(grid);

  if (primaryStyle.materialsTip) {
    const tip = document.createElement("p");
    tip.className = "section-tip";
    tip.textContent = primaryStyle.materialsTip;
    card.appendChild(tip);
  }

  container.appendChild(card);
}

export { renderMaterialsSection };
