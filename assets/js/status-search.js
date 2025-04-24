(function(){
  let currentPage = 1;
  let currentSortBy = 'id';
  let currentSortOrder = 'asc';

  // 検索・ページ切替・ソート切替のメイン処理
  function fetchCharacterStats(page = 1) {
    currentPage = page;
    const rarity     = document.getElementById('rarity').value;
    const version_v1 = document.getElementById('version_v1').checked ? 'v1' : '';
    const version_v2 = document.getElementById('version_v2').checked ? 'v2' : '';
    let query = `?rarity=${rarity}&version_v1=${version_v1}&version_v2=${version_v2}` +
                `&page=${page}&sort_by=${currentSortBy}&sort_order=${currentSortOrder}`;

    ['hp','attack','magic_attack','defense','magic_defense','agility'].forEach(stat => {
      const val  = document.getElementById(stat).value;
      const cond = document.getElementById(stat + '_condition').value;
      if (val !== '') {
        query += `&${stat}=${val}&${stat}_condition=${cond}`;
      }
    });

    fetch(window.location.origin + '/wp-json/character-stats/search' + query)
      .then(r => r.json())
      .then(data => {
        // モバイル幅ならカード表示、デスクトップならテーブル表示
        if (window.innerWidth <= 768) {
          renderCards(data);
        } else {
          renderTable(data);
          renderPagination(data);
          updateSortArrows();
        }
      });
  }

  // テーブル描画
  function renderTable(response) {
    const rows = response.data;
    let html = '<table class="table table-bordered"><thead><tr>' +
               '<th>レアリティ</th>' +
               '<th>バージョン</th>' +
               '<th onclick="toggleSort(\'hp\')">HP <span id="arrow-hp">▲▼</span></th>' +
               '<th onclick="toggleSort(\'attack\')">攻撃 <span id="arrow-attack">▲▼</span></th>' +
               '<th onclick="toggleSort(\'magic_attack\')">魔攻 <span id="arrow-magic_attack">▲▼</span></th>' +
               '<th onclick="toggleSort(\'defense\')">防御 <span id="arrow-defense">▲▼</span></th>' +
               '<th onclick="toggleSort(\'magic_defense\')">魔防 <span id="arrow-magic_defense">▲▼</span></th>' +
               '<th onclick="toggleSort(\'agility\')">敏捷 <span id="arrow-agility">▲▼</span></th>' +
               '<th onclick="toggleSort(\'atk_mgatk\')">攻撃＋魔攻 <span id="arrow-atk_mgatk">▲▼</span></th>' +
               '<th onclick="toggleSort(\'def_mgdef\')">防御＋魔防 <span id="arrow-def_mgdef">▲▼</span></th>' +
               '</tr></thead><tbody>';

    if (rows.length) {
      rows.forEach(r => {
        const sumAtk = Number(r.attack) + Number(r.magic_attack);
        const sumDef = Number(r.defense) + Number(r.magic_defense);

        html += `<tr>
          <td>${r.rarity}</td>
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
      html += `<tr><td colspan="10" class="text-center">該当するキャラクターが見つかりませんでした。</td></tr>`;
    }

    html += '</tbody></table>';
    document.getElementById('search-results').innerHTML = html;
  }

  // ページネーション描画
  function renderPagination(response) {
    const page  = response.current_page;
    const total = response.total_pages;
    let html = '<div class="text-center mt-3">';
    if (page > 1) {
      html += `<button type="button" class="btn btn-secondary" id="prev-page">前へ</button> `;
    }
    html += `<span>ページ ${page} / ${total}</span>`;
    if (page < total) {
      html += ` <button type="button" class="btn btn-secondary" id="next-page">次へ</button>`;
    }
    html += '</div>';

    const container = document.getElementById('search-results');
    container.insertAdjacentHTML('beforeend', html);

    const prev = document.getElementById('prev-page');
    if (prev) prev.onclick = () => fetchCharacterStats(page - 1);
    const next = document.getElementById('next-page');
    if (next) next.onclick = () => fetchCharacterStats(page + 1);
  }

  // カード表示（モバイル用）
  function renderCards(response) {
    const rows = response.data;
    let html = '<div class="row">';
    rows.forEach(r => {
      const sumAtk = Number(r.attack) + Number(r.magic_attack);
      const sumDef = Number(r.defense) + Number(r.magic_defense);

      html += `
        <div class="col-12 mb-2">
          <div class="card">
            <div class="card-body p-2">
              <p class="mb-1"><strong>レアリティ:</strong> ${r.rarity}</p>
              <p class="mb-1"><strong>バージョン:</strong> ${r.version}</p>
              <p class="mb-1"><strong>HP:</strong> ${r.hp}</p>
              <p class="mb-1"><strong>攻撃:</strong> ${r.attack}</p>
              <p class="mb-1"><strong>魔攻:</strong> ${r.magic_attack}</p>
              <p class="mb-1"><strong>防御:</strong> ${r.defense}</p>
              <p class="mb-1"><strong>魔防:</strong> ${r.magic_defense}</p>
              <p class="mb-1"><strong>敏捷:</strong> ${r.agility}</p>
              <p class="mb-1"><strong>攻撃＋魔攻:</strong> ${sumAtk}</p>
              <p class="mb-1"><strong>防御＋魔防:</strong> ${sumDef}</p>
            </div>
          </div>
        </div>`;
    });
    html += '</div>';
    document.getElementById('search-results').innerHTML = html;
  }

  // ソート切り替え
  window.toggleSort = function(stat) {
    if (currentSortBy === stat) {
      currentSortOrder = currentSortOrder === 'asc' ? 'desc' : 'asc';
    } else {
      currentSortBy     = stat;
      currentSortOrder  = 'asc';
    }
    fetchCharacterStats(currentPage);
  };

  // 検索ボタンイベント設定
  document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('search-button');
    if (btn) btn.onclick = () => fetchCharacterStats(1);
  });
})();
