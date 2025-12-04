document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.chooseEat').forEach(item => {
    item.addEventListener('click', () => {
      const old = document.querySelector('.chooseEatSelected');
      if (old) old.classList.remove('chooseEatSelected');
      item.classList.add('chooseEatSelected');
    });
  });
});
