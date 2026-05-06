const tabDefaultMetric = {
  overview: "3da",
  scoring: "wedge20_t20",
  finishing: null,
  insights: null,
  heatmaps: null
};

// Run on page load

document.addEventListener("DOMContentLoaded", async () => {
  initTabs();

  
//   // simulate clicking the default active tab
//   const activeTab = document.querySelector(".tab-btn.active");
//   if (activeTab) {
//     activeTab.click();
//   }
// }); 


  let currentFilters = {
    session: "all",
  };
  let chart3DA = null;
  let activeMetric = "3da";

  let userTargets = {};

  const defaultTargets = {
    "3da": 30,
    scoring3da: 30,
    t20: 10,
    dpl: 30,
    double: 20,
  };

  const metricTargets = {
    "3da": 30,
    scoring3da: 30,
    t20: 10, // example %
    double: 20, // example %
    dpl: 30, // darts per leg target
  };

  const slider = document.getElementById("target-slider");
  const targetValueLabel = document.getElementById("target-value");

  function updateSliderUI(metric) {
    const value = getMetricTarget(metric);

    slider.value = value;
    targetValueLabel.textContent = value;
  }

  slider.addEventListener("change", async () => {
    const metric = activeMetric;
    const value = parseFloat(slider.value);

    // ✅ Update local memory FIRST
    userTargets[metric] = value;

    // ✅ Update label immediately
    targetValueLabel.textContent = value;

    updateTargetLine(activeMetric, value);

    // ✅ Save to DB
    try {
      await fetch("save_user_target.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          metric: metric,
          value: value,
        }),
      });
    } catch (err) {
      console.error("Error saving target:", err);
    }

    // ✅ NOW refresh chart (with updated value)
    loadChartByMetric(metric);
  });

  await loadUserTargets();
  reloadAllStats();
  loadChartByMetric(activeMetric);
  updateSliderUI(activeMetric);

  document.getElementById("session-filter").addEventListener("change", (e) => {
    currentFilters.session = e.target.value;

    reloadAllStats(); // refresh everything
  });

  document.querySelectorAll(".stat-card.selectable").forEach((card) => {
    card.addEventListener("click", () => {
      activeMetric = card.dataset.metric;

      // optional: visual highlight
      document
        .querySelectorAll(".stat-card")
        .forEach((c) => c.classList.remove("active"));
      card.classList.add("active");

      loadChartByMetric(activeMetric);
    });
  });

  async function load3DA() {
    try {
      const res = await fetch(`get_3da.php?session=${currentFilters.session}`);
      const data = await res.json();
      if (data.error) {
        console.error(data.error);
        return;
      }

      document.getElementById("stat-3da").textContent = data.three_dart_avg;
    } catch (err) {
      console.error("Error loading 3DA:", err);
    }
  }

  async function loadDartsPerLeg() {
    try {
      const res = await fetch(
        `get_darts_per_leg.php?session=${currentFilters.session}`
      );
      const data = await res.json();

      console.log("DPL response:", data);

      if (data.error) return;

      document.getElementById("stat-dpl").textContent = data.darts_per_leg;
    } catch (err) {
      console.error("Error loading DPL:", err);
    }
  }

  async function loadDoubleAttempts() {
    try {
      const res = await fetch(
        `get_double_pct.php?session=${currentFilters.session}`
      );
      const data = await res.json();

      console.log("Double Attempts response:", data);

      if (data.error) return;

      const avgAttemptsPerGame = data.double_pct
        ? (data.attempts / data.successes).toFixed(2)
        : "--";

      document.getElementById("stat-doubleAttempts").textContent =
        avgAttemptsPerGame;
    } catch (err) {
      console.error("Error loading double attempts:", err);
    }
  }

  async function loadGamesPlayed() {
    try {
      const res = await fetch(
        `get_games_played.php?session=${currentFilters.session}`
      );
      const data = await res.json();

      document.getElementById("stat-games").textContent = data.games_played;
    } catch (err) {
      console.error("Error loading games played:", err);
    }
  }
  async function loadT20Pct() {
    try {
      const res = await fetch(
        `get_t20_pct.php?session=${currentFilters.session}`
      );
      const data = await res.json();

      console.log("T20 %:", data);

      if (data.error) return;

      document.getElementById("stat-t20").textContent = data.t20_pct + "%";
    } catch (err) {
      console.error("Error loading T20 %:", err);
    }
  }

  async function load3DAChart() {
    try {
      const res = await fetch(
        `get_3da_timeseries.php?session=${currentFilters.session}`
      );
      const json = await res.json();

      if (!chart3DA) {
        initChart(json.labels, json.data);
      } else {
        updateChart(json.labels, json.data);
      }
    } catch (err) {
      console.error("Chart error:", err);
    }
  }

  async function loadScoring3DA() {
    try {
      const res = await fetch(
        `get_scoring_3da.php?session=${currentFilters.session}`
      );
      const data = await res.json();

      document.getElementById("scoring3da-value").innerText =
        data.three_dart_avg ?? 0;
    } catch (err) {
      console.error("Error loading Scoring 3DA:", err);
    }
  }

  function reloadAllStats() {
    load3DA();
    loadDartsPerLeg();
    loadDoubleAttempts();
    loadGamesPlayed();
    loadT20Pct();
    load3DAChart();
    loadScoring3DA();
  }

  function initChart(labels = [], data = [], metric = "3da") {
    const target = getMetricTarget(metric);

    const datasets = [
      {
        label: "Value",
        data: data,
        tension: 0.3,
        pointRadius: 4, // 👈 size of points
        pointHoverRadius: 6, // 👈 size when hovering
      },
    ];

    if (target !== null) {
      datasets.push({
        label: "Target",
        data: labels.map(() => target),
        borderDash: [5, 5],
        pointRadius: 0,
        tension: 0,
      });
    }

    const ctx = document.getElementById("chart-3da").getContext("2d");

    chart3DA = new Chart(ctx, {
      type: "line",
      data: {
        labels: labels,
        datasets: datasets,
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: true },
        },
      },
    });
  }

  function updateChart(labels, data, metric = "3da") {
    const target = getMetricTarget(metric);

    const datasets = [
      {
        label: "Value",
        data: data,
        tension: 0.3,
        pointRadius: 4, // 👈 size of points
        pointHoverRadius: 6, // 👈 size when hovering
      },
    ];

    if (target !== null) {
      datasets.push({
        label: "Target",
        data: labels.map(() => target),
        borderDash: [5, 5],
        pointRadius: 0,
        tension: 0,
      });
    }

    chart3DA.data.labels = labels;
    chart3DA.data.datasets = datasets;
    chart3DA.update();
  }

  async function loadChartByMetric(metric) {
    
    console.log("Loading metric:", metric);
    try {
      const res = await fetch(
        `get_timeseries.php?metric=${metric}&session=${currentFilters.session}`
      );
      const json = await res.json();
      
    console.log("labels:", json.labels);
    console.log("data:", json.data);

      if (!chart3DA) {
        initChart(json.labels, json.data, metric);
      } else {
        updateChart(json.labels, json.data, metric);
      }

      // Update title
      const titles = {
        "3da": "3-Dart Average Over Time",
        t20: "T20 % Over Time",
        dpl: "Darts Per Leg Over Time",
        doubleAttempts: "Double Attempts Over Time",
        scoring3da: "Scoring 3-Dart Average Over Time",
        "wedge20_t20": "20 Wedge % (T20 Target) Over Time",
      };

      document.getElementById("chart-title").textContent =
        titles[metric] || "Statistics Over Time";

      updateSliderUI(metric);
    } catch (err) {
      console.error("Chart load error:", err);
    }
  }

  async function loadUserTargets() {
    try {
      const res = await fetch("get_user_targets.php");
      userTargets = await res.json();
    } catch (err) {
      console.error("Error loading targets:", err);
      userTargets = {};
    }
  }

  function updateSliderUI(metric) {
    const value = getMetricTarget(metric);

    slider.value = value;
    targetValueLabel.textContent = value;
  }

  function getMetricTarget(metric) {
    if (userTargets && userTargets.hasOwnProperty(metric)) {
      return parseFloat(userTargets[metric]);
    }

    if (defaultTargets && defaultTargets.hasOwnProperty(metric)) {
      return defaultTargets[metric];
    }

    return 0;
  }

  function updateTargetLine(metric, targetValue) {
    if (!chart3DA) return;

    const labels = chart3DA.data.labels;

    // Ensure dataset[1] is the target line
    if (chart3DA.data.datasets.length < 2) return;

    chart3DA.data.datasets[1].data = labels.map(() => targetValue);

    chart3DA.update();
  }



function initTabs() {
  document.querySelectorAll(".tab-btn").forEach((btn) => {
    btn.addEventListener("click", () => {

      // active button styling
      document.querySelectorAll(".tab-btn").forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");

      const tab = btn.dataset.tab;

      // show correct tab
      document.querySelectorAll(".tab-content").forEach((el) => {
        el.style.display = "none";
      });

      document.getElementById(tab).style.display = "block";

      // load tab-specific stats (cards)
      if (tab === "scoring") {
        loadScoringStats();
      }

      // 🔥 THIS is the pipeline routing
      const metric = tabDefaultMetric[tab];

      if (metric) {
        loadChartByMetric(metric);
      }
    });
  });
}



  // ADD NEW FUNCTIONS HERE












});


function loadScoringStats() {
  const filter = document.getElementById("session-filter").value;

  fetch("stats/scoring/get_scoring_stats.php?filter=" + filter)
    .then((res) => res.json())
    .then((data) => {
      const el = document.getElementById("stat-s20-t20");

      if (data.s20_when_t20_has_data) {
        el.textContent = data.s20_when_t20_pct.toFixed(2) + "%";
      } else {
        el.textContent = "—";
      }
    })
    .catch((err) => console.error(err));
}
