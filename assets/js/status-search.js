(function(){
  let currentPage = 1;
  let currentSortBy = 'id';
  let currentSortOrder = 'asc';

  function fetchCharacterStats(page = 1) {
    currentPage = page;
    const rarity     = document.getElementById('rarity').value;
    const version_v1 = document.getElementById('version_v1').checked ? 'v1' : '';
    const version_v2 = document.getElementById('version_v2').checked ? 'v2' : '';
    // 検索レアリティ表示
    document.getElementById('current-rarity').textContent = rarity || '—';

    let query = `?rarity=${rarity}&version_v1=${version_v1}&version_v2=${version_v2}` +
                `&page=${page}&sort_by=${currentSortBy}&sort_order=${currentSortOrder}`;
    ['hp','attack','magic_attack','defense','magic_defense','agility'].forEach(stat => {
      const val  = document.getElementById(stat).value;
      const cond = document.getElementById(stat + '_condition').value;
      if (val !== '') query += `&${stat}=${val}&${stat}_condition=${cond}`;
    });

    fetch(window.location.origin + '/wp-json/character-stats/search' + query)
      .then(r => r.json())
      .then(data => {
        renderTable(data);
        renderPagination(data);
        updateSortArrows();
      });
  }

  function renderTable(response) {
    const rows = response.data;
    let html = '<table class="table table-bordered"><thead><tr>' +
               '<th>Ver</th>' +
               '<th onclick="toggleSort(\'hp\')">HP <span id="arrow-hp">▲▼</span></th>' +
               '<th onclick="toggleSort(\'attack\')">攻撃 <span id="arrow-attack">▲▼</span></th>' +
               '<th onclick="toggleSort(\'magic_attack\')">魔攻 <span id="arrow-magic_attack">▲▼</span></th>' +
               '<th onclick="toggleSort(\'defense\')">防御 <span id="arrow-defense">▲▼</span></th>' +
               '<th onclick="toggleSort(\'magic_defense\')">魔防 <span id="arrow-magic_defense">▲▼</span></th>' +
               '<th onclick="toggleSort(\'agility\')">敏捷 <span id="arrow-agility">▲▼</span></th>' +
               '<th onclick="toggleSort(\'atk_mgatk\')">攻撃+<br>魔攻 <span id="arrow-atk_mgatk">▲▼</span></th>' +
               '<th onclick="toggleSort(\'def_mgdef\')">防御+<br>魔防 <span id="arrow-def_mgdef">▲▼</span></th>' +
               '</tr></thead><tbody>';

    if (rows.length) {
      rows.forEach(r => {
        const sumAtk = Number(r.attack) + Number(r.magic_attack);
        const sumDef = Number(r.defense) + Number(r.magic_defense);
        html += `<tr>
          <td>${r.version}</td>
          <td>${r.hp}</td>
          <td>${r.attack}</td>
          <td>${r.magic_attack}</td>
          <td>${r.defense}</td>
          <td>${r.magic_defense}</td>
          <td>${r.agility}</td>
          <td>${sumAtk}</td>
          <td>${sumDef}</td>
        </tr>`;
      });
    } else {
      html += `<tr><td colspan="9" class="text-center">該当するキャラクターが見つかりませんでした。</td></tr>`;
    }

    html += '</tbody></table>';
    document.getElementById('search-results').innerHTML = html;
  }

  function renderPagination(response) {
    const page  = response.current_page;
    const total = response.total_pages;
    let html = '<div class="text-center mt-3">';
    if (page > 1) html += `<button type="button" class="btn btn-secondary" id="prev-page">前へ</button> `;
    html += `<span>ページ ${page} / ${total}</span>`;
    if (page < total) html += ` <button type="button" class="btn btn-secondary" id="next-page">次へ</button>`;
    html += '</div>';
    document.getElementById('search-results').insertAdjacentHTML('beforeend', html);
    const prev = document.getElementById('prev-page'); if (prev) prev.onclick = () => fetchCharacterStats(page - 1);
    const next = document.getElementById('next-page'); if (next) next.onclick = () => fetchCharacterStats(page + 1);
  }

  window.toggleSort = function(stat) {
    if (currentSortBy === stat) currentSortOrder = (currentSortOrder === 'asc' ? 'desc' : 'asc');
    else { currentSortBy = stat; currentSortOrder = 'asc'; }
    fetchCharacterStats(currentPage);
  };

  function updateSortArrows() {
    const stats = ['hp','attack','magic_attack','atk_mgatk','def_mgdef','defense','magic_defense','agility'];
    stats.forEach(stat => {
      const el = document.getElementById(`arrow-${stat}`);
      if (!el) return;
      if (stat === currentSortBy) {
        el.textContent = (currentSortOrder === 'asc' ? ' ▲' : ' ▼');
        el.style.color = 'black';
      } else {
        el.textContent = ' ▲▼'; el.style.color = 'gray';
      }
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('search-button');
    if (btn) btn.onclick = () => fetchCharacterStats(1);
  });
})();
