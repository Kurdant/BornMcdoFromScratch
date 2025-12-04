const produits = document.querySelectorAll('.foodItem');
const modal = document.getElementById('modal');
const modalClose = document.getElementById('closeModal');


/* Open - Close modal */

function openModal() {
  modal.style.display = 'flex';
}


function closeModal() {
  modal.style.display = 'none';
  document.querySelector('.first-step').style.display = 'block';
  document.querySelector('.second-step').style.display = 'none';
  document.querySelector('.third-step').style.display = 'none';
}
modalClose.addEventListener('click', closeModal);


/* Select menu */

fetch('./bd.json')
  .then(response => response.json())
  .then(data => {
    window.jsonData = data;
    createCategories();
    createFoodItems();
    displayFoodByCategory(1); // Affiche la catégorie 1 par défaut
  })
  .catch(error => console.error('Erreur lors du chargement du fichier JSON :', error));

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
    if (cat.id == categoryId) {
      cat.classList.add('categorieItemSelected');
    }
  });
}


function displayFoodByCategory(categoryId) {
  const allFoods = document.querySelectorAll('.foodItem');
  allFoods.forEach(food => {
    food.style.display = food.dataset.category == categoryId ? 'flex' : 'none';
  });
}

function openForMenu() {
  document.querySelector('.first-step').style.display = 'block';
  document.querySelector('.modalBoisson').style.display = 'none';
}

function openForBoisson() {
  document.querySelector('.modalBoisson').style.display = 'block';
  document.querySelector('.first-step').style.display = 'none';
}


function createFoodItems(categoryId) {
  const foodContainer = document.getElementById('foodList');
  const categories = ['menu', 'sandwiches', 'wraps', 'frites', 'boissonsFroides', 'encas', 'desserts'];

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
        if (produit.categoryId === 1) {
          openForMenu()
          openModal()
        } if (produit.categoryId === 5) {
          openForBoisson()
          openModal()
        }
      });
      foodContainer.appendChild(div);
    });
  });
}


/* past every modal */

let menuComposition = [];
let idMenu = null;
console.log(idMenu)

document.querySelectorAll('.modalMenuItem').forEach(item => {
  item.addEventListener('click', () => {

    const old = document.querySelector('.modalMenuItemSelected');
    if (old) old.classList.remove('modalMenuItemSelected');

    item.classList.add('modalMenuItemSelected');

    idMenu = item.id;
    console.log(idMenu);

  });
});


/* boisson modal */

const boissonsContainer = document.getElementById('boissonsMenu');
const leftBoissons = document.querySelector('.boissonsArrowLeft');
const rightBoissons = document.querySelector('.boissonsArrowRight');

let scrollIndex = 0;
const itemWidth = 160; // 150px largeur + 10px margin

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

      container.innerHTML = ''; // Vide le conteneur avant d'ajouter

      boissonsFroides.forEach(produit => {
        const div = document.createElement('div');
        div.classList.add('boissonsFroidMenu');
        div.innerHTML = `
          <img src="./${produit.image}" alt="${produit.nom}" />
          <h3>${produit.nom}</h3>
        `;
        container.appendChild(div);
      });
    })
    .catch(error => console.error(error));
}


document.querySelectorAll('.modalButton.step1').forEach(button => {
  button.addEventListener('click', () => {
    menuComposition.push(idMenu)
    console.log("tableau", menuComposition)
    document.querySelector('.first-step').style.display = 'none';
    document.querySelector('.second-step').style.display = 'block';
  });
});

document.querySelectorAll('.modalButton.step2').forEach(button => {
  button.addEventListener('click', () => {
    menuComposition.push(idMenu);
    console.log("tableau", menuComposition);

    // Passage à la step3
    document.querySelector('.second-step').style.display = 'none';
    document.querySelector('.third-step').style.display = 'block';

    addBoissonMenu(); // Remplir la step3 avec les boissons
  });
});

function addBoissonMenu() {
  fetch('./bd.json')
    .then(response => response.json())
    .then(data => {
      const boissonsFroides = data.boissonsFroides;
      const container = document.getElementById('boissonsMenu');

      container.innerHTML = ''; // Vide le conteneur avant d'ajouter

      boissonsFroides.forEach(produit => {
        const div = document.createElement('div');
        div.classList.add('boissonsFroidMenu');
        div.innerHTML = `
          <img src="./${produit.image}" alt="${produit.nom}" />
          <h3>${produit.nom}</h3>
        `;
        container.appendChild(div);
      });
    })
    .catch(error => console.error(error));
}


document.querySelectorAll('.modalButton.step3').forEach(button => {
  button.addEventListener('click', () => {
    menuComposition.push(idMenu);
    console.log("tableau", menuComposition);
    alert('Menu ajouté à votre commande !');
    document.querySelector('.first-step').style.display = 'block';
    document.querySelector('.third-step').style.display = 'none';
    closeModal();
  });
});



let dataJson = null;

async function createMenus() {
  const r = await fetch('./bd.json');
  dataJson = await r.json();
  console.log(dataJson.menus.accompagnement[1]);
}


/* Change categories using arrows */

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

// 2. Tu appelles ta fonction dans tes event listeners
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
