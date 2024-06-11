import React, { useEffect, useState } from 'react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';
import './App.css';

const App = () => {
  const [data, setData] = useState([]);
  const [days, setDays] = useState(7);

  useEffect(() => {
    fetch(`/wp-json/mc_graph/v1/data?days=${days}`)
     .then((response) => response.json())
     .then((data) => setData(data));
  }, [days]);

const daysChange = (event) => {
  setDays(event.target.value);
};

return (
  <div>
    <div className="graph-widget">
      <label> Graph Widget</label>
      <select id="days" onChange={daysChange} value={days}>
        <option value={7}>Last 7 days</option>
        <option value={15}>Last 15 days</option>
        <option value={30}>Last 1 Month</option>
      </select>
    </div>
    <div style={{ width: '400', height: '300' }}>
      <ResponsiveContainer height="100%" aspect={1}>
        <LineChart
          width={500}
          height={300}
          data={data}
          margin={{
            top: 5,
            right: 30,
            left: 20,
            bottom: 5,
          }}
        >
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis dataKey="date" />
          <YAxis domain={[0,20]} />
          <Tooltip />
          <Legend />
          <Line type="monotone" dataKey="value" stroke="#8884d8" activeDot={{ r: 8 }} />
        </LineChart>
      </ResponsiveContainer>
    </div>
  </div>
  );
};
export default App;
