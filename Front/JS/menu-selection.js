/* ============================================================
MODAL OPEN/CLOSE
============================================================ */

const modal = document.getElementById('modal');
const modalClose = document.getElementById('closeModal');
let menuComposition = [];
let prixFinal = [0]; 
let currentMenuIndexPrix = null; 

let step = 0;

function openModal() {
  modal.style.display = 'flex';
}

function closeModal() {
  modal.style.display = 'none';
  document.querySelector('.first-step').style.display = 'none';
  document.querySelector('.second-step').style.display = 'none';
  document.querySelector('.third-step').style.display = 'none';
  menuComposition.length = 0;
  updateBackDisplay();
  step = 0;
}
modalClose.addEventListener('click', closeModal);

/* ============================================================
LOAD JSON + INITIAL CREATION OF CATEGORIES AND FOOD ITEMS
============================================================ */

fetch('./bd.json')
  .then(response => response.json())
  .then(data => {
    window.jsonData = data;
    createCategories();
    createFoodItems();
    displayFoodByCategory(1);
  })
  .catch(error => console.error('Erreur JSON :', error));

/* ============================================================
CATEGORY CREATION + CATEGORY SELECTION
============================================================ */

const categorieList = document.getElementById('categorieList');

function createCategories() {
  jsonData.categories.forEach((categorie, idx) => {
    const div = document.createElement('div');
    div.classList.add('categorieItem');
    if (idx === 0) div.classList.add('categorieItemSelected');
    div.id = categorie.id;

    const label = document.createElement('div');
    label.classList.add('categorieLabel');
    label.textContent = categorie.nom;

    div.appendChild(label);
    categorieList.appendChild(div);
  });
}

function selectCategory(categoryId) {
  const categories = document.querySelectorAll('.categorieItem');
  categories.forEach(cat => {
    cat.classList.remove('categorieItemSelected');
    if (cat.id == categoryId) cat.classList.add('categorieItemSelected');
  });
}

function displayFoodByCategory(categoryId) {
  const allFoods = document.querySelectorAll('.foodItem');
  allFoods.forEach(food => {
    food.style.display = food.dataset.category == categoryId ? 'flex' : 'none';
  });
}

/* ============================================================
OPEN MODAL FOR MENU OR DRINKS
============================================================ */

function openForMenu() {
  document.querySelector('.first-step').style.display = 'block';
  document.querySelector('.modalBoisson').style.display = 'none';
}

function openForBoisson() {
  document.querySelector('.modalBoisson').style.display = 'block';
  document.querySelector('.first-step').style.display = 'none';
}

/* ============================================================
FOOD ITEMS CREATION
============================================================ */

function createFoodItems() {
  const foodContainer = document.getElementById('foodList');
  const categories = [
    'menu',
    'sandwiches',
    'wraps',
    'frites',
    'boissonsFroides',
    'encas',
    'desserts'
  ];

  categories.forEach(categoryKey => {
    jsonData[categoryKey].forEach(produit => {
      const div = document.createElement('div');
      div.classList.add('foodItem');
      div.dataset.category = produit.categoryId;

      div.innerHTML = `
        <img src="./${produit.image}" alt="${produit.nom}" />
        <h3>${produit.nom}</h3>
        <p>${produit.description}</p>
        <span>${produit.prix}€</span>
      `;

      div.addEventListener('click', () => {
        const nom = produit.nom;
        const prix = produit.prix; // nombre

        // CAS MENU
        if (produit.categoryId === 1) {
          openForMenu();
          openModal();
          menuComposition = [nom];

          const indexPrix = prixFinal.length;
          currentMenuIndexPrix = indexPrix;
          prixFinal.push(prix);
          prixFinalCalc();
          console.log('rajoute du prix MENU dans le tableau ', prixFinal);
          return;
        }

        // CAS BOISSON SEULE
        if (produit.categoryId === 5) {
          openForBoisson();
          openModal();

          AjoutProduitSimple(nom, prix);
          console.log('rajoute du prix BOISSON SEULE dans le tableau ', prixFinal);
          return;
        }

        // CAS PRODUIT SIMPLE
        AjoutProduitSimple(nom, prix);
        console.log('rajoute du prix PRODUIT SIMPLE dans le tableau ', prixFinal);
      });

      foodContainer.appendChild(div);
    });
  });
}

/* ============================================================
MODAL MENU ITEM SELECTION
============================================================ */

let idMenu = null;

document.querySelectorAll('.modalMenuItem').forEach(item => {
  item.addEventListener('click', () => {
    const old = document.querySelector('.modalMenuItemSelected');
    if (old) old.classList.remove('modalMenuItemSelected');

    item.classList.add('modalMenuItemSelected');
    idMenu = item.id;
  });
});

/* ============================================================
BOISSONS SLIDER + LOADING DRINKS IN STEP 3
============================================================ */

const boissonsContainer = document.getElementById('boissonsMenu');
const leftBoissons = document.querySelector('.boissonsArrowLeft');
const rightBoissons = document.querySelector('.boissonsArrowRight');

let scrollIndex = 0;
const itemWidth = 160;

rightBoissons.addEventListener('click', () => {
  scrollIndex++;
  boissonsContainer.scrollTo({ left: scrollIndex * itemWidth, behavior: 'smooth' });
});

leftBoissons.addEventListener('click', () => {
  scrollIndex = Math.max(scrollIndex - 1, 0);
  boissonsContainer.scrollTo({ left: scrollIndex * itemWidth, behavior: 'smooth' });
});

function addBoissonMenu() {
  fetch('./bd.json')
    .then(response => response.json())
    .then(data => {
      const boissonsFroides = data.boissonsFroides;
      const container = document.getElementById('boissonsMenu');

      container.innerHTML = '';

      boissonsFroides.forEach(produit => {
        const div = document.createElement('div');
        div.classList.add('boissonsFroidMenu', 'modalMenuItem');
        div.id = `${produit.id}`;
        div.innerHTML = `
          <img src="./${produit.image}" alt="${produit.nom}" />
          <h3 class='modalMenuLabel'>${produit.nom}</h3>
        `;
        container.appendChild(div);
        div.addEventListener('click', () => {
          const old = container.querySelector('.modalMenuItemSelected');
          if (old) old.classList.remove('modalMenuItemSelected');
          div.classList.add('modalMenuItemSelected');
          idMenu = div.id;
          console.log('Boisson sélectionnée id:', idMenu);
        });
      });
    })
    .catch(error => console.error(error));
}

/* ============================================================
BACK BUTTON DISPLAY (SHOW ONLY IF SELECTION EXISTS)
============================================================ */

const backModal = document.getElementById('backModal');

function updateBackDisplay() {
  backModal.style.display = menuComposition.length === 0 ? 'none' : 'block';
}
updateBackDisplay();

/* ============================================================
SUPPRESSION DANS LE PANIER
============================================================ */

function panierTrash(event) {
  const item = event.target.closest('.panierOrderItem');
  if (!item) return;

  const indexPrix = item.dataset.indexPrix;

  if (indexPrix !== undefined) {
    prixFinal[indexPrix] = 0;
  }

  item.remove();
  prixFinalCalc();
}

document.addEventListener('click', e => {
  if (e.target.classList.contains('panierTrash')) panierTrash(e);
});

/* ============================================================
AJOUT AU PANIER (MENU COMPLET)
============================================================ */

function AjoutAuPanier() {
  const panier = document.querySelector('.panierOrder');

  console.log(menuComposition);

  const typeMenu = menuComposition[1];
  const burger = menuComposition[0];

  const titre = `1 ${typeMenu} ${burger}`;

  const item = document.createElement('div');
  item.classList.add('panierOrderItem');

  item.dataset.indexPrix = currentMenuIndexPrix;

  const title = document.createElement('div');
  title.classList.add('panierOrderTitle');
  title.textContent = titre;

  const trash = document.createElement('span');
  trash.classList.add('panierTrash');
  trash.innerHTML = '&#128465;';

  const ul = document.createElement('ul');

  for (let i = 2; i < menuComposition.length; i++) {
    const li = document.createElement('li');
    li.textContent = menuComposition[i];
    ul.appendChild(li);
  }

  item.appendChild(title);
  item.appendChild(trash);
  item.appendChild(ul);

  panier.appendChild(item);

  currentMenuIndexPrix = null;
}

/* ============================================================
AJOUT PRODUIT SIMPLE
============================================================ */

function AjoutProduitSimple(nom, prix) {
  const panier = document.querySelector('.panierOrder');

  const indexPrix = prixFinal.length;

  prixFinal.push(prix);

  const item = document.createElement('div');
  item.classList.add('panierOrderItem');

  item.dataset.indexPrix = indexPrix;

  const title = document.createElement('div');
  title.classList.add('panierOrderTitle');
  title.textContent = `${nom}`;

  const trash = document.createElement('span');
  trash.classList.add('panierTrash');
  trash.innerHTML = '&#128465;';

  item.appendChild(title);
  item.appendChild(trash);
  panier.appendChild(item);

  prixFinalCalc();
}

/* ============================================================
STEP BUTTON ACTIONS
============================================================ */

const steps = [
  document.querySelector('.first-step'),
  document.querySelector('.second-step'),
  document.querySelector('.third-step')
];
const nextBtn = document.getElementById('nextStep');

function renderStep() {
  steps.forEach((el, i) => {
    el.style.display = i === step ? 'block' : 'none';
  });

  nextBtn.textContent = step === steps.length - 1 ? 'Ajouter à la commande' : 'Étape suivante';
  console.log('Affichage step:', step);
}

function getSelectedChoice() {
  return steps[step].querySelector('.modalMenuItemSelected');
}

function nextStep() {
  const selected = getSelectedChoice();
  if (!selected && step !== steps.length - 1) {
    console.log('Impossible de passer à l’étape suivante : aucun choix sélectionné');
    alert('Veuillez sélectionner un choix avant de continuer.');
    return;
  }
  if (selected) {
    const label = selected.querySelector('.modalMenuLabel').textContent;
    console.log('Ajout du choix sélectionné à menuComposition:', label);
    menuComposition.push(label);
    console.log('menuComposition:', menuComposition);
  }
  if (step < steps.length - 1) {
    step++;
    console.log('Step suivante. Nouvel index:', step);
    if (step === 2) {
      console.log('Chargement des boissons');
      addBoissonMenu();
    }
    renderStep();
    updateBackDisplay();
  } else {
    AjoutAuPanier();
    console.log('Ajout final à la commande terminé');
    closeModal();
    step = 0;
    renderStep();
    menuComposition.length = 0;
    console.log('Steps et tableau réinitialisés');
  }
}

function prevStep() {
  console.log('Retour arrière. Step actuel avant retour:', step);
  if (menuComposition.length > 0) {
    const removed = menuComposition.pop();
    console.log('Suppression du dernier élément ajouté:', removed, menuComposition);
  }
  step = Math.max(0, step - 1);
  console.log('Step après retour:', step);
  renderStep();
  updateBackDisplay();
}

renderStep();

nextBtn.addEventListener('click', nextStep);
document.getElementById('backModal').addEventListener('click', prevStep);

/* ============================================================
ASYNC LOADING FOR MENUS (IF NEEDED LATER)
============================================================ */

let dataJson = null;

async function createMenus() {
  const r = await fetch('./bd.json');
  dataJson = await r.json();
}

/* ============================================================
CHANGE CATEGORIES WITH ARROWS
============================================================ */

const leftArrow = document.querySelector('.categorieArrowLeft');
const rightArrow = document.querySelector('.categorieArrowRight');
let currentCategorieIndex = 0;

function changeCategoriesArrows(direction) {
  const categories = document.querySelectorAll('.categorieItem');
  const total = categories.length;

  if (direction === 'next') {
    currentCategorieIndex = (currentCategorieIndex + 1) % total;
  } else {
    currentCategorieIndex = (currentCategorieIndex - 1 + total) % total;
  }

  const target = categories[currentCategorieIndex];
  selectCategory(target.id);
  displayFoodByCategory(target.id);
}

rightArrow.addEventListener('click', () => {
  changeCategoriesArrows('next');
});

leftArrow.addEventListener('click', () => {
  changeCategoriesArrows('prev');
});

categorieList.addEventListener('click', (event) => {
  const categoryItem = event.target.closest('.categorieItem');
  if (categoryItem) {
    selectCategory(categoryItem.id);
    displayFoodByCategory(categoryItem.id);
  }

  const allCategories = document.querySelectorAll('.categorieItem');
  const index = Array.from(allCategories).indexOf(categoryItem);
  currentCategorieIndex = index;
});

/* ============================================================
CALC FINAL PRICE
============================================================ */

const prixFinalID = document.getElementById('prixFinal');

function prixFinalCalc() {
  const total = prixFinal.reduce((acc, val) => acc + val, 0);
  prixFinalID.textContent = `${total.toFixed(2)}€`;
}
