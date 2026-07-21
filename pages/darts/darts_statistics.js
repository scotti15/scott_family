const tabDefaultMetric = {
  overview: "3da",
  scoring: "wedge20_t20",
  finishing: null,
  insights: null,
  heatmaps: null,
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

    // Scoring
    loadT20DistributionWheel();
    loadScoringStats();

    // Finishing
    loadDoubleTargetWheel();
    loadSetupTargetWheel();
    loadPureDouble();
    loadSetupS();
    loadGameplayDouble();
    loadSetupS();
    loadDPCA();
    loadDPCB();
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
        wedge20_t20: "20 Wedge % (T20 Target) Over Time",
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
        document
          .querySelectorAll(".tab-btn")
          .forEach((b) => b.classList.remove("active"));
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
          renderTargetWheel("t20-distribution-wheel", "t20");
          loadT20DistributionWheel();
        }

        if (tab === "finishing") {
          loadPureDouble();
          loadGameplayDouble();
          loadSetupS();
          loadDPCA();
          loadDPCB();
          renderTargetWheel("double-target-wheel", "double");
          renderTargetWheel("setup-target-wheel", "setup");

          loadDoubleTargetWheel();
          loadSetupTargetWheel();
        }
        // 🔥 THIS is the pipeline routing
        const metric = tabDefaultMetric[tab];

        if (metric) {
          loadChartByMetric(metric);
        }
      });
    });
  }

  function renderTargetWheel(containerId, prefix) {
    const dartOrder = [
      20, 1, 18, 4, 13, 6, 10, 15, 2, 17, 3, 19, 7, 16, 8, 11, 14, 9, 12, 5,
    ];

    const size = 300;
    const center = size / 2;
    const outerRadius = 130;
    const innerRadius = 40; // creates a donut hole for the center summary

    // Helper: convert polar coordinates to cartesian
    function polarToCartesian(cx, cy, r, angleDeg) {
      const angleRad = ((angleDeg - 90) * Math.PI) / 180;
      return {
        x: cx + r * Math.cos(angleRad),
        y: cy + r * Math.sin(angleRad),
      };
    }

    // Helper: create SVG path for one wedge
    function describeArcSegment(cx, cy, rOuter, rInner, startAngle, endAngle) {
      const p1 = polarToCartesian(cx, cy, rOuter, endAngle);
      const p2 = polarToCartesian(cx, cy, rOuter, startAngle);
      const p3 = polarToCartesian(cx, cy, rInner, startAngle);
      const p4 = polarToCartesian(cx, cy, rInner, endAngle);

      const largeArcFlag = endAngle - startAngle <= 180 ? "0" : "1";

      return [
        `M ${p1.x} ${p1.y}`,
        `A ${rOuter} ${rOuter} 0 ${largeArcFlag} 0 ${p2.x} ${p2.y}`,
        `L ${p3.x} ${p3.y}`,
        `A ${rInner} ${rInner} 0 ${largeArcFlag} 1 ${p4.x} ${p4.y}`,
        "Z",
      ].join(" ");
    }

    let svg = `
    <svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}">
  `;

    // Build the 20 wedges
    dartOrder.forEach((num, i) => {
      const startAngle = i * 18 - 9;
      const endAngle = startAngle + 18;

      // ---- THIS IS WHERE THE SVG HTML FOR EACH SEGMENT GOES ----
      const path = describeArcSegment(
        center,
        center,
        outerRadius,
        innerRadius,
        startAngle,
        endAngle
      );

      // Position text roughly in the middle of the wedge
      const midAngle = startAngle + 9;
      const labelRadius = (outerRadius + innerRadius) / 2;

      const labelPos = polarToCartesian(center, center, labelRadius, midAngle);

      svg += `
      <path
        id="${prefix}-segment-${num}"
        d="${path}"
        fill="#e0e0e0"
        stroke="#ffffff"
        stroke-width="1"
      />

      <text
        x="${labelPos.x}"
        y="${labelPos.y}"
        text-anchor="middle"
        dominant-baseline="middle"
        font-size="11"
        font-weight="bold"
        pointer-events="none"
      >
        ${num}
      </text>
    `;
    });

    // Center circle (optional overall percentage later)
    svg += `
      <circle
        cx="${center}"
        cy="${center}"
        r="${innerRadius - 2}"
        fill="white"
        stroke="#ccc"
      />
    </svg>
  `;

    document.getElementById(containerId).innerHTML = svg;
  }

  async function loadT20DistributionWheel() {
    const filter = document.getElementById("session-filter").value;

    try {
      const res = await fetch(
        `stats/scoring/get_T20_target_wedge_distribution.php?filter=${filter}`
      );
      const json = await res.json();

      document
      .querySelectorAll("path[id^='t20-segment']")
      .forEach((segment) => {
        segment.setAttribute("fill", "#eeeeee");
        segment.dataset.tooltip = "No attempts";
      });

      const rows = json.rows;
      const totalAttempts = json.totalAttempts;
      console.log(rows);

      const tooltip = document.getElementById("svg-tooltip");

      rows.forEach((row) => {
        const wedge = Number(row.wedge);
        const hits = Number(row.hits);
        const pct = Number(row.pct);

        const segment = document.getElementById(`t20-segment-${wedge}`);
        if (!segment) return;

        let color = "#cccccc";

        // T20 - higher is better
        if (wedge === 20) {
          if (pct >= 75) color = "#1b5e20"; // Dark Green - Elite
          else if (pct >= 60) color = "#81c784"; // Light Green - Strong
          else if (pct >= 40) color = "#ffeb3b"; // Yellow - Average
          else if (pct >= 30) color = "#ef9a9a"; // Light Red - Needs Work
          else color = "#f44336"; // Red - Major Leak
        }

        // S1 / S5 - adjacent misses
        else if (wedge === 1 || wedge === 5) {
          if (pct <= 5) color = "#1b5e20"; // Dark Green - Elite
          else if (pct <= 10) color = "#81c784"; // Light Green - Strong
          else if (pct <= 15) color = "#ffeb3b"; // Yellow - Average
          else if (pct <= 20) color = "#ef9a9a"; // Light Red - Needs Work
          else color = "#f44336"; // Red - Major Leak
        }

        // 18 / 12 - deeper misses
        else if (wedge === 18 || wedge === 12) {
          if (pct <= 1) color = "#1b5e20"; // Dark Green - Elite
          else if (pct <= 3) color = "#81c784"; // Light Green - Strong
          else if (pct <= 5) color = "#ffeb3b"; // Yellow - Average
          else if (pct <= 8) color = "#ef9a9a"; // Light Red - Needs Work
          else color = "#f44336"; // Red - Major Leak
        }

        // Everything else - wild misses
        else {
          if (pct <= 1) color = "#1b5e20"; // Acceptable
          else color = "#f44336"; // Major Leak
        }

        segment.setAttribute("fill", color);

        segment.dataset.tooltip =
          `Wedge ${wedge}\n` + `${pct.toFixed(2)}%\n` + `${hits} hits\n`;
      });

      const title = document.getElementById("t20-wheel-title");

      if (title) {
        title.textContent = `T20 Wedge Distribution (${totalAttempts} attempts)`;
      }

      document.querySelectorAll("path[id^='t20-segment']").forEach((el) => {
        el.addEventListener("mousemove", (e) => {
          tooltip.style.display = "block";
          tooltip.style.left = e.pageX + 10 + "px";
          tooltip.style.top = e.pageY + 10 + "px";
          tooltip.textContent = el.dataset.tooltip;
        });

        el.addEventListener("mouseleave", () => {
          tooltip.style.display = "none";
        });
      });
    } catch (err) {
      console.error("T20 Distribution Wheel load error:", err);
    }
  }

  async function loadDoubleTargetWheel() {
    try {
      const filter = document.getElementById("session-filter").value;

      const res = await fetch(
        "stats/finishing/get_double_targets.php?filter=" + filter
      );
      const rows = await res.json();

document
  .querySelectorAll("path[id^='double-segment']")
  .forEach((segment) => {
    segment.setAttribute("fill", "#eeeeee");
    segment.dataset.tooltip = "No attempts";
  });

      rows.forEach((row) => {
        const hits = Number(row.hits);
        const target = row.target;
        const pct = Number(row.pct);
        const attempts = Number(row.attempts);

        // Find the SVG segment
        const segment = document.getElementById(`double-segment-${target}`);
        if (!segment) return;

        // Determine fill color
        let color = "#cccccc"; // default

        if (attempts < 1) {
          color = "#eeeeee"; // too little data
        } else if (pct >= 10) {
          color = "#4caf50"; // green
        } else if (pct >= 5) {
          color = "#ffeb3b"; // yellow
        } else {
          color = "#f44336"; // red
        }

        segment.setAttribute("fill", color);

        document
        .querySelectorAll(
          "path[id^='double-segment'], path[id^='double-segment']"
        )
        .forEach((el) => {
          el.addEventListener("mousemove", (e) => {
            tooltip.style.display = "block";
            tooltip.style.left = e.pageX + 10 + "px";
            tooltip.style.top = e.pageY + 10 + "px";
            tooltip.textContent = el.dataset.tooltip;
          });
      
          el.addEventListener("mouseleave", () => {
            tooltip.style.display = "none";
          });
        });
      
        const label = "S";
        const tooltip = document.getElementById("svg-tooltip");

        segment.dataset.tooltip =
          `${label}${target}\n` +
          `${pct.toFixed(2)}%\n` +
          `${hits} hits\n` +
          `${attempts} attempts`;
      });
    } catch (err) {
      console.error("Souble Target Wheel load error:", err);
    }
  }

  async function loadSetupTargetWheel() {
    try {
      const filter = document.getElementById("session-filter").value;

      const res = await fetch(
        "stats/finishing/get_setup_targets.php?filter=" + filter
      );
      const rows = await res.json();

      document
  .querySelectorAll("path[id^='setup-segment']")
  .forEach((segment) => {
    segment.setAttribute("fill", "#eeeeee");
    segment.dataset.tooltip = "No attempts";
  });
      rows.forEach((row) => {
        const hits = Number(row.hits);
        const target = row.target;
        const pct = Number(row.pct);
        const attempts = Number(row.attempts);

        // Find the SVG segment
        const segment = document.getElementById(`setup-segment-${target}`);
        if (!segment) return;

        // Determine fill color
        let color = "#cccccc"; // default

        if (attempts < 1) {
          color = "#eeeeee"; // too little data
        } else if (pct >= 35) {
          color = "#4caf50"; // green
        } else if (pct >= 20) {
          color = "#ffeb3b"; // yellow
        } else {
          color = "#f44336"; // red
        }

        segment.setAttribute("fill", color);

        const label = "S";
        const tooltip = document.getElementById("svg-tooltip");

        document
          .querySelectorAll(
            "path[id^='setup-segment'], path[id^='setup-segment']"
          )
          .forEach((el) => {
            el.addEventListener("mousemove", (e) => {
              tooltip.style.display = "block";
              tooltip.style.left = e.pageX + 10 + "px";
              tooltip.style.top = e.pageY + 10 + "px";
              tooltip.textContent = el.dataset.tooltip;
            });

            el.addEventListener("mouseleave", () => {
              tooltip.style.display = "none";
            });
          });

        segment.dataset.tooltip =
          `${label}${target}\n` +
          `${pct.toFixed(2)}%\n` +
          `${hits} hits\n` +
          `${attempts} attempts`;
      });
    } catch (err) {
      console.error("Setup Target Wheel load error:", err);
    }
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

async function loadPureDouble() {
  try {
    const filter = document.getElementById("session-filter").value;

    const res = await fetch(
      "stats/finishing/get_pure_double.php?filter=" + filter);
    const json = await res.json();
    const pct = Number(json.pure_double_pct ?? 0);

    document.getElementById("stat-pure-double").textContent =
      (json.pure_double_pct ?? 0) + "%";
    // Derived stat: attempts per success
    const attemptsPerSuccess = pct > 0 ? 100 / pct : 0;

    document.getElementById(
      "stat-pure-double-effort"
    ).textContent = `(~${attemptsPerSuccess.toFixed(1)} attempts)`;
  } catch (err) {
    console.error("Pure Double load error:", err);
  }
}

async function loadGameplayDouble() {
  try {
    const filter = document.getElementById("session-filter").value;

    const res = await fetch(
      "stats/finishing/get_gameplay_double.php?filter=" + filter
    );
    const json = await res.json();
    const pct = Number(json.gameplay_double_pct ?? 0);

    // Main stat
    document.getElementById("stat-gameplay-double").textContent =
      pct.toFixed(1) + "%";

    // Derived stat: attempts per success
    const attemptsPerSuccess = pct > 0 ? 100 / pct : 0;

    document.getElementById(
      "stat-gameplay-double-effort"
    ).textContent = `(~${attemptsPerSuccess.toFixed(1)} attempts)`;
  } catch (err) {
    console.error("Gameplay Double load error:", err);
  }
}

async function loadSetupS() {
  try {
    const filter = document.getElementById("session-filter").value;

    const res = await fetch(
      "stats/finishing/get_setup_s.php?filter=" + filter
    );

    const json = await res.json();

    document.getElementById("stat-setup-s").textContent =
      (json.setup_s_pct ?? 0) + "%";
  } catch (err) {
    console.error("Setup S load error:", err);
  }
}
async function loadDPCA() {
  try {
    const filter = document.getElementById("session-filter").value;

    const res = await fetch(
      "stats/finishing/get_dpc_a.php?filter=" + filter
    );
    const json = await res.json();

    document.getElementById("stat-dpc-a").textContent = json.dpc_a ?? "--";
  } catch (err) {
    console.error("DPC-A load error:", err);
  }
}

async function loadDPCB() {
  try {
    const filter = document.getElementById("session-filter").value;

    const res = await fetch(
      "stats/finishing/get_dpc_b.php?filter=" + filter
    );
    const json = await res.json();

    document.getElementById("stat-dpc-b").textContent = json.dpc_b ?? "--";
  } catch (err) {
    console.error("DPC-B load error:", err);
  }
}
